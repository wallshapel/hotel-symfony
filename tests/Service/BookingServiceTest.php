<?php

namespace App\Tests\Service;

use App\Contract\HotelImageInterface;
use App\Entity\Booking;
use App\Entity\Hotel;
use App\Entity\Image;
use App\Entity\Room;
use App\Entity\User;
use App\Service\BookingService;
use App\Service\RoomService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingServiceTest extends TestCase
{
    private $em;
    private $validator;
    private $tokenStorage;
    private $roomService;
    private $hotelImageInterface;
    private BookingService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->roomService = $this->createMock(RoomService::class);
        $this->hotelImageInterface = $this->createMock(HotelImageInterface::class);

        $this->service = new BookingService(
            $this->em,
            $this->validator,
            $this->tokenStorage,
            $this->roomService,
            $this->hotelImageInterface
        );
    }

    public function testGetAllReservationsPaginatedReturnsExpectedData(): void
    {
        $bookingMock = $this->createMock(Booking::class);
        $roomMock = $this->createMock(Room::class);
        $hotelMock = $this->createMock(Hotel::class);

        $bookingMock->method('getId')->willReturn(1);
        $bookingMock->method('getStartDate')->willReturn(new \DateTime('2025-04-01'));
        $bookingMock->method('getEndDate')->willReturn(new \DateTime('2025-04-03'));
        $bookingMock->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2025-04-01 12:00:00'));
        $bookingMock->method('getRoom')->willReturn($roomMock);

        $roomMock->method('getId')->willReturn(10);
        $roomMock->method('getNumber')->willReturn('101');
        $roomMock->method('getType')->willReturn('Double');
        $roomMock->method('getCapacity')->willReturn(2);
        $roomMock->method('getPrice')->willReturn(120.50);
        $roomMock->method('getStatus')->willReturn('available');
        $roomMock->method('getHotel')->willReturn($hotelMock);

        $hotelMock->method('getId')->willReturn(5);
        $hotelMock->method('getName')->willReturn('Hotel Demo');
        $hotelMock->method('getCity')->willReturn('Madrid');
        $hotelMock->method('getCountry')->willReturn('España');

        // Simular imágenes
        $this->roomService
            ->method('getRoomImages')
            ->with(10)
            ->willReturn([
                ['id' => 1, 'filename' => 'room.jpg']
            ]);

        $this->hotelImageInterface
            ->method('getHotelImages')
            ->with(5)
            ->willReturn([
                ['id' => 1, 'filename' => 'hotel.jpg']
            ]);

        // Simular resultado del query de bookings
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())
            ->method('getResult')
            ->willReturn([$bookingMock]);

        // Simular resultado del query de conteo
        $countQueryMock = $this->createMock(Query::class);
        $countQueryMock->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        // Mock para query de listado
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('from')->willReturnSelf();
        $qbMock->method('join')->willReturnSelf();
        $qbMock->method('orderBy')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        // Mock para query de conteo
        $countQbMock = $this->createMock(QueryBuilder::class);
        $countQbMock->method('select')->willReturnSelf();
        $countQbMock->method('from')->willReturnSelf();
        $countQbMock->method('getQuery')->willReturn($countQueryMock);

        // Retornar los query builders en orden
        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qbMock, $countQbMock);

        // Ejecutar
        $filters = ['page' => 1, 'limit' => 10];
        $result = $this->service->getAllReservationsPaginated($filters);

        // Verificaciones
        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(1, $result['pagination']['total']);
        $this->assertEquals('Hotel Demo', $result['data'][0]['hotel']['name']);
    }

    public function testGetPastBookingsPaginatedReturnsExpectedData(): void
    {
        $bookingMock = $this->createMock(Booking::class);
        $roomMock = $this->createMock(Room::class);
        $hotelMock = $this->createMock(Hotel::class);

        $bookingMock->method('getId')->willReturn(2);
        $bookingMock->method('getStartDate')->willReturn(new \DateTime('2023-01-01'));
        $bookingMock->method('getEndDate')->willReturn(new \DateTime('2023-01-05'));
        $bookingMock->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2022-12-31 10:00:00'));
        $bookingMock->method('getRoom')->willReturn($roomMock);

        $roomMock->method('getId')->willReturn(20);
        $roomMock->method('getNumber')->willReturn('202');
        $roomMock->method('getType')->willReturn('Suite');
        $roomMock->method('getCapacity')->willReturn(3);
        $roomMock->method('getPrice')->willReturn(200.0);
        $roomMock->method('getHotel')->willReturn($hotelMock);

        $hotelMock->method('getId')->willReturn(15);
        $hotelMock->method('getName')->willReturn('Past Hotel');
        $hotelMock->method('getCity')->willReturn('Lisboa');
        $hotelMock->method('getCountry')->willReturn('Portugal');

        $this->roomService
            ->method('getRoomImages')
            ->with(20)
            ->willReturn([
                ['id' => 2, 'filename' => 'old-room.jpg']
            ]);

        $this->hotelImageInterface
            ->method('getHotelImages')
            ->with(15)
            ->willReturn([
                ['id' => 3, 'filename' => 'old-hotel.jpg']
            ]);

        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())
            ->method('getResult')
            ->willReturn([$bookingMock]);

        $countQueryMock = $this->createMock(Query::class);
        $countQueryMock->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('from')->willReturnSelf();
        $qbMock->method('join')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('orderBy')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $countQbMock = $this->createMock(QueryBuilder::class);
        $countQbMock->method('select')->willReturnSelf();
        $countQbMock->method('from')->willReturnSelf();
        $countQbMock->method('where')->willReturnSelf();
        $countQbMock->method('setParameter')->willReturnSelf();
        $countQbMock->method('getQuery')->willReturn($countQueryMock);

        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qbMock, $countQbMock);

        $result = $this->service->getPastBookingsPaginated(['page' => 1, 'limit' => 10]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(1, $result['pagination']['total']);
        $this->assertEquals('Past Hotel', $result['data'][0]['hotel']['name']);
    }

    public function testGetCurrentBookingsPaginatedReturnsExpectedData(): void
    {
        $bookingMock = $this->createMock(Booking::class);
        $roomMock = $this->createMock(Room::class);
        $hotelMock = $this->createMock(Hotel::class);

        $roomImageMock = $this->createConfiguredMock(Image::class, [
            'getId' => 1,
            'getFilename' => 'room.jpg',
            'getOriginalName' => 'Room Original'
        ]);

        $hotelImageMock = $this->createConfiguredMock(Image::class, [
            'getId' => 2,
            'getFilename' => 'hotel.jpg',
            'getOriginalName' => 'Hotel Original'
        ]);

        // Simular colección de imágenes
        $roomImages = new ArrayCollection([$roomImageMock]);
        $hotelImages = new ArrayCollection([$hotelImageMock]);

        // Relaciones
        $bookingMock->method('getId')->willReturn(3);
        $bookingMock->method('getStartDate')->willReturn(new \DateTimeImmutable('2025-04-01'));
        $bookingMock->method('getEndDate')->willReturn(new \DateTimeImmutable('2025-04-10'));
        $bookingMock->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2025-04-01 08:00:00'));
        $bookingMock->method('getRoom')->willReturn($roomMock);

        $roomMock->method('getId')->willReturn(30);
        $roomMock->method('getNumber')->willReturn('303');
        $roomMock->method('getType')->willReturn('Individual');
        $roomMock->method('getCapacity')->willReturn(1);
        $roomMock->method('getPrice')->willReturn(80.0);
        $roomMock->method('getImages')->willReturn($roomImages);
        $roomMock->method('getHotel')->willReturn($hotelMock);

        $hotelMock->method('getId')->willReturn(25);
        $hotelMock->method('getName')->willReturn('Now Hotel');
        $hotelMock->method('getCity')->willReturn('Roma');
        $hotelMock->method('getCountry')->willReturn('Italia');
        $hotelMock->method('getImages')->willReturn($hotelImages);

        // Mock para el query de datos
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())
            ->method('getResult')
            ->willReturn([$bookingMock]);

        // Mock para el query de conteo
        $countQueryMock = $this->createMock(Query::class);
        $countQueryMock->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        // QueryBuilder para bookings
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('from')->willReturnSelf();
        $qbMock->method('join')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        // QueryBuilder para conteo
        $countQbMock = $this->createMock(QueryBuilder::class);
        $countQbMock->method('select')->willReturnSelf();
        $countQbMock->method('from')->willReturnSelf();
        $countQbMock->method('where')->willReturnSelf();
        $countQbMock->method('andWhere')->willReturnSelf();
        $countQbMock->method('setParameter')->willReturnSelf();
        $countQbMock->method('getQuery')->willReturn($countQueryMock);

        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qbMock, $countQbMock);

        $result = $this->service->getCurrentBookingsPaginated(['page' => 1, 'limit' => 10]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Now Hotel', $result['data'][0]['hotel']['name']);
        $this->assertEquals('/uploads/images/rooms/room.jpg', $result['data'][0]['room']['images'][0]['url']);
    }

    public function testGetFutureBookingsPaginatedReturnsExpectedData(): void
    {
        $bookingMock = $this->createMock(Booking::class);
        $roomMock = $this->createMock(Room::class);
        $hotelMock = $this->createMock(Hotel::class);
        $userMock = $this->createMock(User::class);
        $imageRoom = $this->createConfiguredMock(Image::class, [
            'getId' => 1,
            'getFilename' => 'future-room.jpg',
            'getOriginalName' => 'Future Room'
        ]);
        $imageHotel = $this->createConfiguredMock(\App\Entity\Image::class, [
            'getId' => 2,
            'getFilename' => 'future-hotel.jpg',
            'getOriginalName' => 'Future Hotel'
        ]);

        $roomImages = new ArrayCollection([$imageRoom]);
        $hotelImages = new ArrayCollection([$imageHotel]);

        // Mock del usuario
        $userMock->method('getId')->willReturn(99);
        $userMock->method('getUserIdentifier')->willReturn('futurist');

        // Mock del booking
        $bookingMock->method('getId')->willReturn(4);
        $bookingMock->method('getStartDate')->willReturn(new \DateTime('2025-12-01'));
        $bookingMock->method('getEndDate')->willReturn(new \DateTime('2025-12-10'));
        $bookingMock->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2025-11-01 10:00:00'));
        $bookingMock->method('getRoom')->willReturn($roomMock);
        $bookingMock->method('getUser')->willReturn($userMock);

        // Mock del room
        $roomMock->method('getId')->willReturn(40);
        $roomMock->method('getNumber')->willReturn('404');
        $roomMock->method('getType')->willReturn('Futuristic');
        $roomMock->method('getCapacity')->willReturn(4);
        $roomMock->method('getPrice')->willReturn(999.99);
        $roomMock->method('getImages')->willReturn($roomImages);
        $roomMock->method('getHotel')->willReturn($hotelMock);

        // Mock del hotel
        $hotelMock->method('getId')->willReturn(35);
        $hotelMock->method('getName')->willReturn('Hotel Futuro');
        $hotelMock->method('getCity')->willReturn('Tokio');
        $hotelMock->method('getCountry')->willReturn('Japón');
        $hotelMock->method('getImages')->willReturn($hotelImages);

        // Mock del query de resultados
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())
            ->method('getResult')
            ->willReturn([$bookingMock]);

        // Mock del query de conteo
        $countQueryMock = $this->createMock(Query::class);
        $countQueryMock->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        // Mock de los query builders
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('from')->willReturnSelf();
        $qbMock->method('join')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('orderBy')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $countQbMock = $this->createMock(QueryBuilder::class);
        $countQbMock->method('select')->willReturnSelf();
        $countQbMock->method('from')->willReturnSelf();
        $countQbMock->method('where')->willReturnSelf();
        $countQbMock->method('setParameter')->willReturnSelf();
        $countQbMock->method('getQuery')->willReturn($countQueryMock);

        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qbMock, $countQbMock);

        $result = $this->service->getFutureBookingsPaginated(['page' => 1, 'limit' => 10]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Hotel Futuro', $result['data'][0]['hotel']['name']);
        $this->assertEquals('futurist', $result['data'][0]['user']['username']);
        $this->assertEquals('/uploads/images/hotels/future-hotel.jpg', $result['data'][0]['hotel']['images'][0]['url']);
    }

    public function testCreateReturnsUnauthorizedWhenUserIsNull(): void
    {
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn(null);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $result = $this->service->create([
            'room_id' => 1,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-05'
        ]);

        $this->assertEquals(401, $result['status']);
        $this->assertEquals('Unauthorized', $result['message']);
    }

    public function testCreateReturnsNotFoundWhenRoomDoesNotExist(): void
    {
        $userMock = $this->createMock(User::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        // Simulamos que no se encuentra la habitación
        $roomRepoMock = $this->createMock(EntityRepository::class);
        $roomRepoMock->method('find')->willReturn(null);

        $this->em
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepoMock);

        $result = $this->service->create([
            'room_id' => 999,
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-10'
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Room not found.', $result['message']);
    }

    public function testCreateReturnsBadRequestWhenDatesAreInvalid(): void
    {
        $userMock = $this->createMock(User::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        // Simular repositorio que sí encuentra una habitación
        $roomMock = $this->createMock(Room::class);

        $roomRepoMock = $this->createMock(EntityRepository::class);
        $roomRepoMock->method('find')->willReturn($roomMock);

        $this->em
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepoMock);

        // Fechas inválidas
        $result = $this->service->create([
            'room_id' => 1,
            'start_date' => 'invalid-date',
            'end_date' => 'another-invalid-date'
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid date format.', $result['message']);
    }

    public function testCreateReturnsConflictWhenBookingOverlaps(): void
    {
        $userMock = $this->createMock(User::class);
        $roomMock = $this->createMock(Room::class);
        $existingBooking = $this->createMock(Booking::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        // Mock repo de Room
        $roomRepoMock = $this->createMock(EntityRepository::class);
        $roomRepoMock->method('find')->willReturn($roomMock);

        // Mock repo de Booking
        $bookingQbMock = $this->createMock(QueryBuilder::class);
        $bookingQbMock->method('andWhere')->willReturnSelf();
        $bookingQbMock->method('setParameter')->willReturnSelf();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getOneOrNullResult')->willReturn($existingBooking);

        $bookingQbMock->method('getQuery')->willReturn($queryMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('createQueryBuilder')->willReturn($bookingQbMock);

        $this->em
            ->method('getRepository')
            ->willReturnMap([
                [Room::class, $roomRepoMock],
                [Booking::class, $bookingRepoMock]
            ]);

        $result = $this->service->create([
            'room_id' => 1,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-10'
        ]);

        $this->assertEquals(409, $result['status']);
        $this->assertEquals('This room is already booked during the selected period.', $result['message']);
    }

    public function testCreateReturnsBadRequestWhenValidationFails(): void
    {
        $userMock = $this->createMock(User::class);
        $roomMock = $this->createMock(Room::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $roomRepoMock = $this->createMock(EntityRepository::class);
        $roomRepoMock->method('find')->willReturn($roomMock);

        $bookingQbMock = $this->createMock(QueryBuilder::class);
        $bookingQbMock->method('andWhere')->willReturnSelf();
        $bookingQbMock->method('setParameter')->willReturnSelf();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getOneOrNullResult')->willReturn(null); // no hay conflicto

        $bookingQbMock->method('getQuery')->willReturn($queryMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('createQueryBuilder')->willReturn($bookingQbMock);

        $this->em
            ->method('getRepository')
            ->willReturnMap([
                [Room::class, $roomRepoMock],
                [Booking::class, $bookingRepoMock]
            ]);

        // Simular error de validación
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('startDate');
        $violation->method('getMessage')->willReturn('This value should not be blank.');

        $violationList = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violationList);

        $result = $this->service->create([
            'room_id' => 1,
            'start_date' => '2025-09-01',
            'end_date' => '2025-09-10'
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('startDate', $result['errors']);
        $this->assertEquals('This value should not be blank.', $result['errors']['startDate']);
    }

    public function testCreateReturnsCreatedWhenBookingIsValid(): void
    {
        $userMock = $this->createMock(User::class);
        $roomMock = $this->createMock(Room::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $roomRepoMock = $this->createMock(EntityRepository::class);
        $roomRepoMock->method('find')->willReturn($roomMock);

        $bookingQbMock = $this->createMock(QueryBuilder::class);
        $bookingQbMock->method('andWhere')->willReturnSelf();
        $bookingQbMock->method('setParameter')->willReturnSelf();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getOneOrNullResult')->willReturn(null); // sin conflicto

        $bookingQbMock->method('getQuery')->willReturn($queryMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('createQueryBuilder')->willReturn($bookingQbMock);

        $this->em
            ->method('getRepository')
            ->willReturnMap([
                [Room::class, $roomRepoMock],
                [Booking::class, $bookingRepoMock]
            ]);

        // Simular validador sin errores
        $violationList = new ConstraintViolationList([]);
        $this->validator
            ->method('validate')
            ->willReturn($violationList);

        // Esperamos que persist() y flush() se llamen 1 vez
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->create([
            'room_id' => 1,
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-10'
        ]);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals('Booking created successfully.', $result['message']);
    }

    public function testUpdateReturnsUnauthorizedWhenUserIsNull(): void
    {
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn(null);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $result = $this->service->update(1, [
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-10'
        ]);

        $this->assertEquals(401, $result['status']);
        $this->assertEquals('Unauthorized', $result['message']);
    }

    public function testUpdateReturnsNotFoundWhenBookingDoesNotExist(): void
    {
        $userMock = $this->createMock(User::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn(null);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        $result = $this->service->update(123, [
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-10'
        ]);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Booking not found.', $result['message']);
    }

    public function testUpdateReturnsForbiddenWhenUserIsNotOwnerOrAdmin(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $otherUserMock = $this->createMock(User::class);
        $otherUserMock->method('getId')->willReturn(2);

        $roomMock = $this->createMock(Room::class);

        $bookingMock = $this->createMock(Booking::class);
        $bookingMock->method('getUser')->willReturn($otherUserMock);
        $bookingMock->method('getRoom')->willReturn($roomMock);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($bookingMock);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        $result = $this->service->update(123, [
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-10'
        ]);

        $this->assertEquals(403, $result['status']);
        $this->assertEquals('Forbidden. You can only modify your own bookings.', $result['message']);
    }

    public function testUpdateReturnsBadRequestWhenDatesAreInvalid(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $bookingMock = $this->createMock(Booking::class);
        $bookingMock->method('getUser')->willReturn($userMock);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($bookingMock);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        $result = $this->service->update(123, [
            'start_date' => 'fecha-mala',
            'end_date' => 'otra-fecha-mala'
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Invalid date format.', $result['message']);
    }

    public function testUpdateReturnsConflictWhenOverlappingBookingExists(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $roomMock = $this->createMock(Room::class);

        $bookingMock = $this->createMock(Booking::class);
        $bookingMock->method('getUser')->willReturn($userMock);
        $bookingMock->method('getId')->willReturn(123);
        $bookingMock->method('getRoom')->willReturn($roomMock);

        $conflictingBooking = $this->createMock(Booking::class);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        // Mock del repositorio principal
        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($bookingMock);

        // Mock del query builder
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getOneOrNullResult')->willReturn($conflictingBooking);

        $qbMock->method('getQuery')->willReturn($queryMock);

        $bookingRepoMock->method('createQueryBuilder')->willReturn($qbMock);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        $result = $this->service->update(123, [
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10'
        ]);

        $this->assertEquals(409, $result['status']);
        $this->assertEquals('This room is already booked during the selected period.', $result['message']);
    }

    public function testUpdateReturnsBadRequestWhenValidationFails(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $roomMock = $this->createMock(Room::class);

        // Usar instancia real de Booking
        $booking = new Booking();
        $booking->setUser($userMock);
        $booking->setRoom($roomMock);

        // Setear ID manualmente
        $ref = new \ReflectionClass($booking);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($booking, 123);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($booking);

        // Mock del QueryBuilder sin conflicto
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getOneOrNullResult')->willReturn(null);

        $qbMock->method('getQuery')->willReturn($queryMock);
        $bookingRepoMock->method('createQueryBuilder')->willReturn($qbMock);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        // Simular error de validación
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('endDate');
        $violation->method('getMessage')->willReturn('End date must be after start date.');

        $violationList = new ConstraintViolationList([$violation]);
        $this->validator
            ->method('validate')
            ->willReturn($violationList);

        $result = $this->service->update(123, [
            'start_date' => '2025-12-10',
            'end_date' => '2025-12-05'
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('endDate', $result['errors']);
        $this->assertEquals('End date must be after start date.', $result['errors']['endDate']);
    }

    public function testUpdateReturnsSuccessWhenDataIsValid(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $roomMock = $this->createMock(Room::class);

        // Instancia real de Booking
        $booking = new Booking();
        $booking->setUser($userMock);
        $booking->setRoom($roomMock);
        $booking->setStartDate(new \DateTime('2025-12-01'));
        $booking->setEndDate(new \DateTime('2025-12-10'));

        // Setear ID manualmente
        $ref = new \ReflectionClass($booking);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($booking, 123);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($booking);

        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getOneOrNullResult')->willReturn(null); // sin conflicto

        $qbMock->method('getQuery')->willReturn($queryMock);
        $bookingRepoMock->method('createQueryBuilder')->willReturn($qbMock);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        // Validación sin errores
        $violationList = new ConstraintViolationList([]);
        $this->validator
            ->method('validate')
            ->willReturn($violationList);

        // Esperamos que flush se llame una vez
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->update(123, [
            'start_date' => '2025-12-15',
            'end_date' => '2025-12-20'
        ]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Booking updated successfully.', $result['message']);
    }

    public function testDeleteReturnsUnauthorizedWhenUserIsNull(): void
    {
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn(null);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $result = $this->service->delete(1);

        $this->assertEquals(401, $result['status']);
        $this->assertEquals('Unauthorized', $result['message']);
    }

    public function testDeleteReturnsNotFoundWhenBookingDoesNotExist(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn(null);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        $result = $this->service->delete(999); // id que no existe

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Booking not found.', $result['message']);
    }

    public function testDeleteReturnsForbiddenWhenUserIsNotOwnerOrAdmin(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $otherUserMock = $this->createMock(User::class);
        $otherUserMock->method('getId')->willReturn(2);

        $roomMock = $this->createMock(Room::class);

        $bookingMock = $this->createMock(Booking::class);
        $bookingMock->method('getUser')->willReturn($otherUserMock); // No es el mismo dueño
        $bookingMock->method('getRoom')->willReturn($roomMock);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($bookingMock);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        $result = $this->service->delete(123); // id de la reserva

        $this->assertEquals(403, $result['status']);
        $this->assertEquals('Forbidden. You can only delete your own bookings.', $result['message']);
    }

    public function testDeleteReturnsSuccessWhenUserIsAuthorized(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $userMock->method('getRoles')->willReturn(['ROLE_USER']);

        $roomMock = $this->createMock(Room::class);

        // Instancia real de Booking para evitar mocks de setters
        $booking = new Booking();
        $booking->setUser($userMock);
        $booking->setRoom($roomMock);

        // Setear ID manualmente
        $ref = new \ReflectionClass($booking);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($booking, 123);

        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($userMock);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($tokenMock);

        $bookingRepoMock = $this->createMock(EntityRepository::class);
        $bookingRepoMock->method('find')->willReturn($booking);

        $this->em
            ->method('getRepository')
            ->with(Booking::class)
            ->willReturn($bookingRepoMock);

        // Verificamos que se llamen remove y flush
        $this->em->expects($this->once())->method('remove')->with($booking);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->delete(123);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Booking deleted successfully.', $result['message']);
    }
}
