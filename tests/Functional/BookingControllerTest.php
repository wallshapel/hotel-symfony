<?php

namespace App\Tests\Functional\Controller;

use App\Contract\BookingInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\CustomWebTestCase;

class BookingControllerTest extends CustomWebTestCase
{
    public function testListAllReturnsBookingsForAdmin(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        $mockData = [
            'data' => [['id' => 1, 'start_date' => '2025-01-01']],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'count' => 1,
                'total' => 1,
                'totalPages' => 1,
            ],
            'status' => 200
        ];

        $bookingService = $this->createMock(BookingInterface::class);
        $bookingService->method('getAllReservationsPaginated')->willReturn($mockData);

        self::getContainer()->set(BookingInterface::class, $bookingService);

        $client->request('GET', '/api/v1/bookings/all?page=1&limit=10');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($mockData, $responseData);
    }

    public function testGetPastBookingsReturnsDataForAdmin(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        // Simulamos el servicio con una respuesta esperada
        self::getContainer()->set(BookingInterface::class, new class implements BookingInterface {
            public function getPastBookingsPaginated(array $filters): array
            {
                return [
                    'data' => [['id' => 101, 'start_date' => '2024-01-01']],
                    'pagination' => ['page' => 1, 'limit' => 10, 'count' => 1, 'total' => 1, 'totalPages' => 1],
                    'status' => 200
                ];
            }

            // Métodos vacíos para cumplir la interfaz
            public function getAllReservationsPaginated(array $filters): array {}
            public function getCurrentBookingsPaginated(array $filters): array {}
            public function getFutureBookingsPaginated(array $filters): array {}
            public function create(array $data): array {}
            public function update(int $bookingId, array $data): array {}
            public function delete(int $bookingId): array {}
        });

        $client->request('GET', '/api/v1/bookings/past');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(101, $responseData['data'][0]['id']);
    }

    public function testGetCurrentBookingsReturnsDataForAdmin(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        self::getContainer()->set(BookingInterface::class, new class implements BookingInterface {
            public function getCurrentBookingsPaginated(array $filters): array
            {
                return [
                    'data' => [['id' => 202, 'start_date' => '2025-04-01']],
                    'pagination' => ['page' => 1, 'limit' => 10, 'count' => 1, 'total' => 1, 'totalPages' => 1],
                    'status' => 200
                ];
            }

            public function getAllReservationsPaginated(array $filters): array {}
            public function getPastBookingsPaginated(array $filters): array {}
            public function getFutureBookingsPaginated(array $filters): array {}
            public function create(array $data): array {}
            public function update(int $bookingId, array $data): array {}
            public function delete(int $bookingId): array {}
        });

        $client->request('GET', '/api/v1/bookings/current');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(202, $responseData['data'][0]['id']);
    }

    public function testGetFutureBookingsReturnsDataForAdmin(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        self::getContainer()->set(BookingInterface::class, new class implements BookingInterface {
            public function getFutureBookingsPaginated(array $filters): array
            {
                return [
                    'data' => [['id' => 303, 'start_date' => '2025-12-01']],
                    'pagination' => ['page' => 1, 'limit' => 10, 'count' => 1, 'total' => 1, 'totalPages' => 1],
                    'status' => 200
                ];
            }

            public function getAllReservationsPaginated(array $filters): array {}
            public function getPastBookingsPaginated(array $filters): array {}
            public function getCurrentBookingsPaginated(array $filters): array {}
            public function create(array $data): array {}
            public function update(int $bookingId, array $data): array {}
            public function delete(int $bookingId): array {}
        });

        $client->request('GET', '/api/v1/bookings/future');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(303, $responseData['data'][0]['id']);
    }

    public function testCreateBookingReturnsCreatedForUser(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        self::getContainer()->set(BookingInterface::class, new class implements BookingInterface {
            public function create(array $data): array
            {
                return [
                    'message' => 'Booking created successfully.',
                    'status' => 201
                ];
            }

            public function getAllReservationsPaginated(array $filters): array {}
            public function getPastBookingsPaginated(array $filters): array {}
            public function getCurrentBookingsPaginated(array $filters): array {}
            public function getFutureBookingsPaginated(array $filters): array {}
            public function update(int $bookingId, array $data): array {}
            public function delete(int $bookingId): array {}
        });

        $payload = [
            'room_id' => 1,
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-10'
        ];

        $client->request(
            'POST',
            '/api/v1/booking',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Booking created successfully.', $responseData['message']);
    }

    public function testUpdateBookingReturnsSuccessForUser(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        self::getContainer()->set(BookingInterface::class, new class implements BookingInterface {
            public function update(int $bookingId, array $data): array
            {
                return [
                    'message' => 'Booking updated successfully.',
                    'status' => 200
                ];
            }

            public function getAllReservationsPaginated(array $filters): array {}
            public function getPastBookingsPaginated(array $filters): array {}
            public function getCurrentBookingsPaginated(array $filters): array {}
            public function getFutureBookingsPaginated(array $filters): array {}
            public function create(array $data): array {}
            public function delete(int $bookingId): array {}
        });

        $payload = [
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-10'
        ];

        $client->request(
            'PATCH',
            '/api/v1/booking/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Booking updated successfully.', $responseData['message']);
    }

    public function testDeleteBookingReturnsSuccessForUser(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        self::getContainer()->set(BookingInterface::class, new class implements BookingInterface {
            public function delete(int $bookingId): array
            {
                return [
                    'message' => 'Booking deleted successfully.',
                    'status' => 200
                ];
            }

            public function getAllReservationsPaginated(array $filters): array {}
            public function getPastBookingsPaginated(array $filters): array {}
            public function getCurrentBookingsPaginated(array $filters): array {}
            public function getFutureBookingsPaginated(array $filters): array {}
            public function create(array $data): array {}
            public function update(int $bookingId, array $data): array {}
        });

        $client->request('DELETE', '/api/v1/booking/123');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Booking deleted successfully.', $responseData['message']);
    }
}
