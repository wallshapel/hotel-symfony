<?php

namespace Tests\Functional;

use App\Repository\HotelRepository;
use App\Repository\ImageRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class RoomImageControllerTest extends CustomWebTestCase
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

    public function testAdminCanUploadRoomImage(): void
    {
        $client = static::createClient();

        // Crear un hotel
        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ],
            json_encode([
                'name' => 'Room Image Hotel',
                'address' => 'Address',
                'city' => 'City',
                'country' => 'Country',
                'description' => 'Hotel for room image upload'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $hotelId = self::getContainer()->get(HotelRepository::class)
            ->findOneBy(['name' => 'Room Image Hotel'])->getId();

        // Crear una habitación asociada al hotel
        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ],
            json_encode([
                'number' => '101',
                'type' => 'Suite',
                'capacity' => 2,
                'price' => 150,
                'status' => 'available',
                'hotel_id' => $hotelId
            ])
        );

        $roomId = $this->getLastInsertedRoomId();

        // Crear imagen simulada
        $tempFile = tempnam(sys_get_temp_dir(), 'room_img');
        imagepng(imagecreatetruecolor(10, 10), $tempFile);
        $file = new UploadedFile($tempFile, 'room.png', 'image/png', null, true);

        // Subir la imagen
        $client->request(
            'POST',
            "/api/v1/room/image/{$roomId}/upload",
            [],
            ['image' => $file],
            [
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Images uploaded successfully.', $data['message']);
    }

    public function testAdminCanUpdateRoomImage(): void
    {
        $client = static::createClient();

        // Crear hotel
        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ],
            json_encode([
                'name' => 'Hotel for Room Image Update',
                'address' => 'RoomImg St.',
                'city' => 'ImgCity',
                'country' => 'Imageland',
                'description' => 'Hotel for room image update test.'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Crear habitación
        $hotelId = $this->getLastInsertedHotelId();
        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ],
            json_encode([
                'number' => '501',
                'type' => 'Executive',
                'capacity' => 4,
                'price' => 300.00,
                'status' => 'available',
                'hotel_id' => $hotelId
            ])
        );

        $roomId = $this->getLastInsertedRoomId();

        // Subir imagen
        $tempFile = tempnam(sys_get_temp_dir(), 'test_room_image');
        imagepng(imagecreatetruecolor(10, 10), $tempFile);
        $file = new UploadedFile($tempFile, 'room.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/room/image/{$roomId}/upload",
            [],
            ['image' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN']
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // 🔍 Obtener la imagen directamente desde la base de datos
        $image = self::getContainer()
            ->get(ImageRepository::class)
            ->findOneBy(['room' => $roomId]);

        $this->assertNotNull($image, 'No image found for the room, upload might have failed.');
        $imageId = $image->getId();

        // Actualizar imagen
        $newTempFile = tempnam(sys_get_temp_dir(), 'updated_room_image');
        imagepng(imagecreatetruecolor(20, 20), $newTempFile);
        $newFile = new UploadedFile($newTempFile, 'updated.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/room/image/{$imageId}/update",
            [],
            ['image' => $newFile],
            ['HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN']
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Room image updated successfully.', $content['message']);
    }

    public function testCanGetRoomImages(): void
    {
        $client = static::createClient();

        // Crear un hotel
        $client->request(
            'POST',
            '/api/v1/hotel/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ],
            json_encode([
                'name' => 'Room Image Hotel GET',
                'address' => 'Img St.',
                'city' => 'Testopolis',
                'country' => 'Testania',
                'description' => 'Hotel to test room image GET'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
        $hotelId = $this->getLastInsertedHotelId();

        // Crear habitación
        $client->request(
            'POST',
            '/api/v1/room',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ],
            json_encode([
                'number' => '303',
                'type' => 'Double',
                'capacity' => 2,
                'price' => 120.00,
                'status' => 'available',
                'hotel_id' => $hotelId
            ])
        );

        $roomId = $this->getLastInsertedRoomId();

        // Subir imagen
        $tempFile = tempnam(sys_get_temp_dir(), 'room_img_get');
        imagepng(imagecreatetruecolor(10, 10), $tempFile);
        $file = new UploadedFile($tempFile, 'get_room.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/room/image/{$roomId}/upload",
            [],
            ['image' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN']
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            "/api/v1/room/image/{$roomId}"
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertIsArray($content);
        $this->assertNotEmpty($content);
        $this->assertArrayHasKey('id', $content[0]);
        $this->assertArrayHasKey('filename', $content[0]);
        $this->assertArrayHasKey('originalName', $content[0]);
        $this->assertArrayHasKey('url', $content[0]);
    }

    public function testGetRoomImagesReturnsNotFoundWhenRoomDoesNotExist(): void
    {
        $client = static::createClient();

        // Usamos un ID que seguramente no existe (por ejemplo, 999999)
        $nonExistentRoomId = 999999;

        $client->request('GET', "/api/v1/room/image/{$nonExistentRoomId}");

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Room not found.', $data['message']);
    }
}
