<?php

namespace App\Tests\Service;

use App\DataTransformer\HotelInputTransformer;
use App\Entity\Hotel;
use App\Normalizer\ValidationErrorNormalizer;
use App\Repository\HotelRepository;
use App\Service\HotelService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HotelServiceTest extends TestCase
{
    private $em;
    private $hotelRepository;
    private $validator;
    private HotelService $hotelService;
    private $transformer;
    private $errorNormalizer;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hotelRepository = $this->createMock(HotelRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->transformer = $this->createMock(HotelInputTransformer::class);
        $this->errorNormalizer = $this->createMock(ValidationErrorNormalizer::class);

        $this->hotelService = new HotelService(
            $this->em,
            $this->hotelRepository,
            $this->validator,
            $this->transformer,
            $this->errorNormalizer
        );
    }

    public function testCreateHotelSuccessfully(): void
    {
        $data = [
            'name' => 'Test Hotel',
            'address' => '123 Street',
            'city' => 'Cityville',
            'country' => 'Testland',
            'description' => 'A nice hotel.'
        ];

        $hotel = new Hotel();

        // Simulamos el transformador: convierte array en objeto Hotel
        $this->transformer
            ->expects($this->once())
            ->method('fromArray')
            ->with($data)
            ->willReturn($hotel);

        // Simulamos que la validación no tiene errores
        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($hotel)
            ->willReturn(new ConstraintViolationList());

        // Esperamos que persist y flush sean llamados
        $this->em->expects($this->once())->method('persist')->with($hotel);
        $this->em->expects($this->once())->method('flush');

        $result = $this->hotelService->create($data);

        $this->assertEquals('Hotel created successfully.', $result['message']);
        $this->assertEquals(JsonResponse::HTTP_CREATED, $result['status']);
    }

    public function testCreateHotelWithValidationErrors(): void
    {
        $data = [
            'name' => '', // inválido (por ejemplo: campo requerido)
        ];

        $hotel = new Hotel();

        // Simulamos transformación exitosa
        $this->transformer
            ->expects($this->once())
            ->method('fromArray')
            ->with($data)
            ->willReturn($hotel);

        // Simulamos errores de validación
        $violationList = $this->createMock(ConstraintViolationList::class);
        $violationList->method('count')->willReturn(1);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($hotel)
            ->willReturn($violationList);

        // Simulamos normalización de errores
        $this->errorNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($violationList)
            ->willReturn(['name' => 'This value should not be blank.']);

        // No se deben llamar persist ni flush
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $result = $this->hotelService->create($data);

        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals(['name' => 'This value should not be blank.'], $result['errors']);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $result['status']);
    }

    public function testDeleteHotelNotFound(): void
    {
        $hotelId = 999;

        // Simulamos que el repositorio no encuentra el hotel
        $this->em
            ->method('getRepository')
            ->willReturn($this->hotelRepository);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with($hotelId)
            ->willReturn(null);

        // No se deben llamar remove ni flush
        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $result = $this->hotelService->delete($hotelId);

        $this->assertEquals('Hotel not found.', $result['message']);
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $result['status']);
    }

    public function testDeleteHotelSuccessfully(): void
    {
        $hotelId = 1;
        $hotel = new Hotel();

        // Simulamos que el repositorio devuelve un hotel
        $this->em
            ->method('getRepository')
            ->willReturn($this->hotelRepository);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with($hotelId)
            ->willReturn($hotel);

        // Esperamos que se llame remove y flush
        $this->em->expects($this->once())->method('remove')->with($hotel);
        $this->em->expects($this->once())->method('flush');

        $result = $this->hotelService->delete($hotelId);

        $this->assertEquals('Hotel deleted successfully.', $result['message']);
        $this->assertEquals(JsonResponse::HTTP_OK, $result['status']);
    }

    public function testUpdateHotelNotFound(): void
    {
        $hotelId = 123;
        $data = ['name' => 'Updated Name'];

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with($hotelId)
            ->willReturn(null);

        // No debe llamarse a flush
        $this->em->expects($this->never())->method('flush');

        $result = $this->hotelService->update($hotelId, $data);

        $this->assertEquals('Hotel not found.', $result['message']);
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $result['status']);
    }

    public function testUpdateHotelWithValidationErrors(): void
    {
        $hotelId = 1;
        $data = ['name' => ''];

        $hotel = new Hotel();

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with($hotelId)
            ->willReturn($hotel);

        // Simulamos que el transformer aplica cambios
        $this->transformer
            ->expects($this->once())
            ->method('updateFromArray')
            ->with($hotel, $data)
            ->willReturn($hotel);

        // Simulamos que hay errores de validación
        $violationList = $this->createMock(ConstraintViolationList::class);
        $violationList->method('count')->willReturn(1);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($hotel)
            ->willReturn($violationList);

        $this->errorNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($violationList)
            ->willReturn(['name' => 'This value should not be blank.']);

        $this->em->expects($this->never())->method('flush');

        $result = $this->hotelService->update($hotelId, $data);

        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals(['name' => 'This value should not be blank.'], $result['errors']);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $result['status']);
    }

    public function testUpdateHotelSuccessfully(): void
    {
        $hotelId = 1;
        $data = ['name' => 'Updated Hotel'];

        $hotel = new Hotel();

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with($hotelId)
            ->willReturn($hotel);

        $this->transformer
            ->expects($this->once())
            ->method('updateFromArray')
            ->with($hotel, $data)
            ->willReturn($hotel);

        // Simulamos que no hay errores de validación
        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($hotel)
            ->willReturn(new ConstraintViolationList());

        $this->em->expects($this->once())->method('flush');

        $result = $this->hotelService->update($hotelId, $data);

        $this->assertEquals('Hotel updated successfully.', $result['message']);
        $this->assertEquals(JsonResponse::HTTP_OK, $result['status']);
    }
}
