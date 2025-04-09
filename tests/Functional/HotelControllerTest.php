<?php

namespace Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

class HotelControllerTest extends CustomWebTestCase
{
    public function testAdminCanCreateHotel(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        $payload = [
            'name' => 'Test Hotel',
            'address' => '123 Example St',
            'city' => 'Cityville',
            'country' => 'Testland',
            'description' => 'A very nice hotel.'
        ];

        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Hotel created successfully.', $content['message']);
    }

    public function testAdminCanUpdateHotel(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        // Primero creamos un hotel para poder actualizarlo
        $payload = [
            'name' => 'Original Hotel',
            'address' => 'Old Street',
            'city' => 'Oldtown',
            'country' => 'Oldland',
            'description' => 'Original description.'
        ];

        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Simulamos que el ID 1 fue creado. En escenarios reales, se debería consultar el ID dinámicamente.
        $hotelId = 1;

        $updatePayload = [
            'name' => 'Updated Hotel Name',
            'description' => 'Updated description.'
        ];

        $client->request(
            'PATCH',
            "/api/v1/hotel/{$hotelId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatePayload)
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Hotel updated successfully.', $content['message']);
    }

    public function testAdminCanDeleteHotel(): void
    {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        $payload = [
            'name' => 'Hotel to Delete',
            'address' => 'Delete St',
            'city' => 'Gone City',
            'country' => 'Nowhere',
            'description' => 'Temporary hotel.'
        ];

        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Extraemos el ID usando el repositorio directamente
        $container = static::getContainer();
        $hotelRepository = $container->get(\App\Repository\HotelRepository::class);
        $hotel = $hotelRepository->findOneBy(['name' => 'Hotel to Delete']);

        $this->assertNotNull($hotel, 'Hotel was not found after creation.');
        $hotelId = $hotel->getId();

        $client->request('DELETE', "/api/v1/hotel/{$hotelId}");
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Hotel deleted successfully.', $content['message']);
    }
}
