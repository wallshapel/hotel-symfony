<?php

namespace App\Tests\Service;

use App\Contract\HotelImageInterface;
use App\DataTransformer\RoomInputTransformer;
use App\Entity\Hotel;
use App\Entity\Image;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Service\RoomService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RoomServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private RoomRepository $roomRepository;
    private HotelImageInterface $hotelImageService;
    private RoomService $roomService;
    private RoomInputTransformer $transformer;

    private function createConstraintViolation(string $propertyPath, string $message): ConstraintViolationInterface
    {
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn($propertyPath);
        $violation->method('getMessage')->willReturn($message);
        return $violation;
    }

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->roomRepository = $this->createMock(RoomRepository::class);
        $this->hotelImageService = $this->createMock(HotelImageInterface::class);
        $this->transformer = $this->createMock(RoomInputTransformer::class);

        $this->roomService = new RoomService(
            $this->em,
            $this->validator,
            $this->roomRepository,
            $this->hotelImageService,
            $this->transformer
        );
    }

    public function testCreateReturnsNotFoundWhenHotelDoesNotExist(): void
    {
        $hotelRepositoryMock = $this->createMock(EntityRepository::class);
        $hotelRepositoryMock->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo(\App\Entity\Hotel::class))
            ->willReturn($hotelRepositoryMock);

        $data = [
            'hotel_id' => 999,
            'number' => '103',
            'type' => 'double',
            'capacity' => 3,
            'price' => 180.0,
        ];

        $result = $this->roomService->create($data);

        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $result['status']);
        $this->assertEquals('Hotel not found.', $result['message']);
    }

    public function testCreateReturnsValidationErrors(): void
    {
        $data = [
            'hotel_id' => 1,
            'number' => '',
            'type' => '',
            'capacity' => 0,
            'price' => 0.0,
        ];

        $mockHotel = $this->createMock(Hotel::class);
        $mockRoom = $this->createMock(Room::class);

        // Mock del repositorio para devolver un hotel válido
        $hotelRepository = $this->createMock(EntityRepository::class);
        $hotelRepository->expects($this->once())
            ->method('find')
            ->with($data['hotel_id'])
            ->willReturn($mockHotel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Hotel::class)
            ->willReturn($hotelRepository);

        // Mock del transformer para que devuelva una entidad Room
        $this->transformer->expects($this->once())
            ->method('fromArray')
            ->with($data, $mockHotel)
            ->willReturn($mockRoom);

        // Simulamos que el validador devuelve errores
        $violationList = new ConstraintViolationList([
            $this->createConstraintViolation('number', 'This value should not be blank.'),
            $this->createConstraintViolation('type', 'This value should not be blank.')
        ]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($mockRoom)
            ->willReturn($violationList);

        $result = $this->roomService->create($data);

        $this->assertSame(JsonResponse::HTTP_BAD_REQUEST, $result['status']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('number', $result['errors']);
        $this->assertArrayHasKey('type', $result['errors']);
        $this->assertSame('This value should not be blank.', $result['errors']['number']);
    }


    public function testCreateReturnsCreatedWhenRoomIsValid(): void
    {
        $data = [
            'hotel_id' => 1,
            'number' => '101',
            'type' => 'Deluxe',
            'capacity' => 2,
            'price' => 150.0,
            'status' => 'available'
        ];

        $mockHotel = $this->createMock(Hotel::class);
        $mockRoom = $this->createMock(Room::class);

        // Mock del repositorio para devolver el hotel existente
        $hotelRepository = $this->createMock(EntityRepository::class);
        $hotelRepository->expects($this->once())
            ->method('find')
            ->with($data['hotel_id'])
            ->willReturn($mockHotel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Hotel::class)
            ->willReturn($hotelRepository);

        // Mock del transformer para que devuelva una entidad Room
        $this->transformer->expects($this->once())
            ->method('fromArray')
            ->with($data, $mockHotel)
            ->willReturn($mockRoom);

        // Simulamos que el validador no devuelve errores
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($mockRoom)
            ->willReturn(new ConstraintViolationList());

        // Persistencia y flush deben ser llamados
        $this->em->expects($this->once())->method('persist')->with($mockRoom);
        $this->em->expects($this->once())->method('flush');

        $result = $this->roomService->create($data);

        $this->assertSame(JsonResponse::HTTP_CREATED, $result['status']);
        $this->assertSame('Room created successfully.', $result['message']);
    }

    public function testGetByIdReturnsNotFoundWhenRoomDoesNotExist(): void
    {
        $this->roomRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->roomService->getById(999);

        $this->assertSame(JsonResponse::HTTP_NOT_FOUND, $result['status']);
        $this->assertSame('Room not found.', $result['message']);
    }

    public function testGetByIdReturnsRoomDataWhenRoomExists(): void
    {
        $room = $this->createMock(Room::class);
        $hotel = $this->createMock(Hotel::class);

        $room->method('getId')->willReturn(1);
        $room->method('getNumber')->willReturn('101');
        $room->method('getType')->willReturn('double');
        $room->method('getCapacity')->willReturn(2);
        $room->method('getPrice')->willReturn(150.00);
        $room->method('getStatus')->willReturn('available');
        $room->method('getImages')->willReturn(new ArrayCollection());
        $room->method('getHotel')->willReturn($hotel);

        $hotel->method('getId')->willReturn(10);
        $hotel->method('getName')->willReturn('Test Hotel');
        $hotel->method('getCity')->willReturn('Test City');

        $this->roomRepository
            ->method('find')
            ->with(1)
            ->willReturn($room);

        $this->hotelImageService
            ->method('getHotelImages')
            ->with(10)
            ->willReturn([
                ['filename' => 'image.jpg']
            ]);

        $result = $this->roomService->getById(1);

        $this->assertSame(JsonResponse::HTTP_OK, $result['status_code']);
        $this->assertSame('101', $result['number']);
        $this->assertSame('Test Hotel', $result['hotel']['name']);
        $this->assertCount(1, $result['hotel']['images']);
    }

    public function testGetRoomImagesReturnsDataWhenRoomExists(): void
    {
        $image = $this->createMock(Image::class);
        $image->method('getId')->willReturn(1);
        $image->method('getFilename')->willReturn('room.jpg');
        $image->method('getOriginalName')->willReturn('original_room.jpg');

        $room = $this->createMock(Room::class);
        $room->method('getImages')->willReturn(new ArrayCollection([$image]));

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->method('find')->with(1)->willReturn($room);

        $this->em
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $result = $this->roomService->getRoomImages(1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('room.jpg', $result[0]['filename']);
        $this->assertSame('original_room.jpg', $result[0]['originalName']);
        $this->assertSame('/uploads/images/rooms/room.jpg', $result[0]['url']);
    }

    public function testDeleteReturnsNotFoundWhenRoomDoesNotExist(): void
    {
        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->method('find')->with(99)->willReturn(null);

        $this->em
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $result = $this->roomService->delete(99);

        $this->assertSame(JsonResponse::HTTP_NOT_FOUND, $result['status']);
        $this->assertSame('Room not found.', $result['message']);
    }

    public function testDeleteRemovesRoomSuccessfully(): void
    {
        $room = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->method('find')->with(1)->willReturn($room);

        $this->em
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $this->em->expects($this->once())->method('remove')->with($room);
        $this->em->expects($this->once())->method('flush');

        $result = $this->roomService->delete(1);

        $this->assertSame(JsonResponse::HTTP_OK, $result['status']);
        $this->assertSame('Room deleted successfully.', $result['message']);
    }

    public function testUpdateReturnsNotFoundWhenRoomDoesNotExist(): void
    {
        $this->em
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($this->createConfiguredMock(EntityRepository::class, [
                'find' => null,
            ]));

        $data = [
            'number' => '105',
            'type' => 'Double',
            'capacity' => 2,
            'price' => 120.0,
            'status' => 'available',
        ];

        $result = $this->roomService->update(999, $data);

        $this->assertSame(JsonResponse::HTTP_NOT_FOUND, $result['status']);
        $this->assertSame('Room not found.', $result['message']);
    }

    public function testUpdateReturnsValidationErrorsWhenRoomIsInvalid(): void
    {
        $room = $this->createMock(Room::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('find')->willReturn($room);
        $this->em->method('getRepository')->with(Room::class)->willReturn($repository);

        // Mock del transformer para que devuelva la misma instancia de Room modificada
        $this->transformer
            ->expects($this->once())
            ->method('updateFromArray')
            ->with($room, ['number' => ''])
            ->willReturn($room);

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getPropertyPath')->willReturn('number');
        $violation->method('getMessage')->willReturn('This value should not be blank.');

        $violations->method('getIterator')->willReturn(new \ArrayIterator([$violation]));

        $this->validator->method('validate')->willReturn($violations);

        $data = ['number' => ''];

        $result = $this->roomService->update(1, $data);

        $this->assertSame(JsonResponse::HTTP_BAD_REQUEST, $result['status']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('number', $result['errors']);
        $this->assertSame('This value should not be blank.', $result['errors']['number']);
    }

    public function testUpdateReturnsOkWhenRoomIsValid(): void
    {
        $room = $this->createMock(Room::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('find')->willReturn($room);
        $this->em->method('getRepository')->with(Room::class)->willReturn($repository);

        $data = [
            'number' => '101',
            'type' => 'Deluxe',
            'capacity' => 2,
            'price' => 200.00,
            'status' => 'available'
        ];

        // Mock del transformer para que devuelva la entidad modificada
        $this->transformer
            ->expects($this->once())
            ->method('updateFromArray')
            ->with($room, $data)
            ->willReturn($room);

        // Validador devuelve que no hay errores
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->em->expects($this->once())->method('flush');

        $result = $this->roomService->update(1, $data);

        $this->assertSame(JsonResponse::HTTP_OK, $result['status']);
        $this->assertSame('Room updated successfully.', $result['message']);
    }
}
