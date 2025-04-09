<?php

namespace Tests\Functional;

use App\Repository\HotelRepository;
use Symfony\Component\HttpFoundation\Response;

class RoomControllerTest extends CustomWebTestCase
{

    protected function getLastInsertedRoomId(): int
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();

        $sql = 'SELECT id FROM room ORDER BY id DESC LIMIT 1';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        return (int) $result->fetchOne();
    }

    protected function getLastInsertedHotelId(): int
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();

        $sql = 'SELECT id FROM hotel ORDER BY id DESC LIMIT 1';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        return (int) $result->fetchOne();
    }

    public function testGetAvailablePaginatedReturnsOk(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/v1/rooms/available',
            [
                'start_date' => '2025-05-01',
                'end_date' => '2025-05-10',
                'page' => 1,
                'limit' => 5
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertArrayHasKey('status', $data);
    }

    public function testAdminCanCreateRoom(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        // Preparamos el hotel antes, ya que la habitación requiere uno existente
        $hotelPayload = [
            'name' => 'Test Hotel for Room',
            'address' => 'Room St. 123',
            'city' => 'Roomville',
            'country' => 'Testland',
            'description' => 'Hotel for room creation test.'
        ];

        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($hotelPayload)
        );

        $hotelResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Obtenemos el ID del hotel recién creado desde la base de datos
        $hotelId = self::getContainer()->get(HotelRepository::class)
            ->findOneBy(['name' => $hotelPayload['name']])
            ->getId();

        $roomPayload = [
            'number' => '101',
            'type' => 'Suite',
            'capacity' => 2,
            'price' => 120.50,
            'hotel_id' => $hotelId,
            'status' => 'available'
        ];

        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($roomPayload)
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Room created successfully.', $data['message']);
    }

    public function testCanGetRoomById(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        // Creamos primero un hotel (precondición)
        $hotelPayload = [
            'name' => 'Test Hotel',
            'address' => 'Example 456',
            'city' => 'Testville',
            'country' => 'Testonia',
            'description' => 'Hotel for room testing'
        ];

        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($hotelPayload)
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Creamos una habitación asociada al hotel
        $hotelId = $this->getLastInsertedHotelId(); // Método auxiliar que tú puedes definir
        $roomPayload = [
            'number' => '301',
            'type' => 'Standard',
            'capacity' => 3,
            'price' => 99.99,
            'hotel_id' => $hotelId,
            'status' => 'available'
        ];

        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($roomPayload)
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Obtener el ID de la habitación recién creada (usa un método auxiliar si es necesario)
        $roomId = $this->getLastInsertedRoomId(); // Puedes extraerlo de la DB o mockearlo

        // Test: ahora accedemos al endpoint GET /api/v1/room/{id}
        $client->request('GET', "/api/v1/room/{$roomId}");

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertEquals('301', $content['number']);
        $this->assertEquals('Standard', $content['type']);
    }

    public function testCanGetRoomImages(): void
    {
        $client = static::createClient();

        // Primero creamos un hotel
        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'name' => 'Test Hotel',
                'address' => '123 Street',
                'city' => 'Testville',
                'country' => 'Testland',
                'description' => 'Hotel for testing rooms'
            ])
        );

        // Creamos una habitación asociada a ese hotel
        $hotelId = $this->getLastInsertedHotelId();

        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'number' => '101',
                'type' => 'Suite',
                'capacity' => 2,
                'price' => 150.00,
                'status' => 'available',
                'hotel_id' => $hotelId
            ])
        );

        $roomId = $this->getLastInsertedRoomId();

        // Ahora obtenemos las imágenes de la habitación (aunque no tenga)
        $client->request('GET', "/api/v1/rooms/{$roomId}/images");

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testAdminCanDeleteRoom(): void
    {
        $client = static::createClient();

        // Crear hotel primero
        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'name' => 'Hotel for Room Deletion',
                'address' => 'Address',
                'city' => 'Roomville',
                'country' => 'Testonia',
                'description' => 'Hotel with a deletable room'
            ])
        );

        $hotelId = $this->getLastInsertedHotelId();

        // Crear habitación asociada
        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'number' => '201',
                'type' => 'Deluxe',
                'capacity' => 2,
                'price' => 250.00,
                'status' => 'available',
                'hotel_id' => $hotelId
            ])
        );

        $roomId = $this->getLastInsertedRoomId();

        // Hacer la petición DELETE
        $client->request(
            'DELETE',
            "/api/v1/room/{$roomId}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN']
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Room deleted successfully.', $content['message']);
    }

    public function testAdminCanUpdateRoom(): void
    {
        $client = static::createClient();

        // Crear hotel
        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'name' => 'Hotel for Update',
                'address' => 'Update Address',
                'city' => 'Update City',
                'country' => 'Update Country',
                'description' => 'Hotel before updating room'
            ])
        );

        $hotelId = $this->getLastInsertedHotelId();

        // Crear habitación asociada
        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'number' => '301',
                'type' => 'Standard',
                'capacity' => 3,
                'price' => 120.00,
                'status' => 'available',
                'hotel_id' => $hotelId
            ])
        );

        $roomId = $this->getLastInsertedRoomId();

        // Actualizar habitación
        $client->request(
            'PATCH',
            "/api/v1/room/{$roomId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'],
            json_encode([
                'number' => '301A',
                'price' => 150.00,
                'status' => 'maintenance'
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Room updated successfully.', $content['message']);
    }
}
