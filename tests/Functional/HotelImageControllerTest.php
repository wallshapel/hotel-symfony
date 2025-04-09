<?php

namespace Tests\Functional;

use App\Repository\HotelRepository;
use App\Repository\ImageRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class HotelImageControllerTest extends CustomWebTestCase
{
    public function testAdminCanUploadHotelImage(): void
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
                'name' => 'Image Upload Hotel',
                'address' => 'Upload St.',
                'city' => 'Upload City',
                'country' => 'Uploadland',
                'description' => 'Hotel to test image upload.'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Obtener hotel ID
        $hotelId = self::getContainer()
            ->get(HotelRepository::class)
            ->findOneBy(['name' => 'Image Upload Hotel'])
            ->getId();

        // Crear archivo simulado
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        imagepng(imagecreatetruecolor(10, 10), $tempFile);
        $file = new UploadedFile($tempFile, 'test.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/hotel/image/{$hotelId}/upload",
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

    public function testAdminCanUpdateHotelImage(): void
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
                'name' => 'Hotel for Image Update',
                'address' => 'Update St.',
                'city' => 'Update City',
                'country' => 'Updateland',
                'description' => 'Hotel with image to update.'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Obtener hotel ID
        $hotelId = self::getContainer()
            ->get(HotelRepository::class)
            ->findOneBy(['name' => 'Hotel for Image Update'])
            ->getId();

        // Subir imagen inicial
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        imagepng(imagecreatetruecolor(10, 10), $tempFile);
        $file = new UploadedFile($tempFile, 'original.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/hotel/image/{$hotelId}/upload",
            [],
            ['image' => $file],
            [
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ]
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Obtener ID de la imagen recién subida
        $imageId = self::getContainer()
            ->get(ImageRepository::class)
            ->findOneBy(['hotel' => $hotelId])
            ->getId();

        // Subir nueva imagen para reemplazarla
        $newTempFile = tempnam(sys_get_temp_dir(), 'updated_image');
        imagepng(imagecreatetruecolor(20, 20), $newTempFile);
        $newFile = new UploadedFile($newTempFile, 'updated.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/hotel/image/{$imageId}/update",
            [],
            ['image' => $newFile],
            [
                'HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN'
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Hotel image updated successfully.', $data['message']);
    }

    public function testCanGetHotelImages(): void
    {
        $client = static::createClient();

        // Paso 1: Crear hotel
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
                'name' => 'Hotel with Images',
                'address' => 'Image St.',
                'city' => 'Imgville',
                'country' => 'Imageland',
                'description' => 'Hotel to test image retrieval.'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Paso 2: Obtener el ID del hotel recién creado
        $hotelId = self::getContainer()
            ->get(HotelRepository::class)
            ->findOneBy(['name' => 'Hotel with Images'])
            ->getId();

        // Paso 3: Subir una imagen
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        imagepng(imagecreatetruecolor(10, 10), $tempFile);
        $file = new UploadedFile($tempFile, 'test.png', 'image/png', null, true);

        $client->request(
            'POST',
            "/api/v1/hotel/image/{$hotelId}/upload",
            [],
            ['image' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer FAKE_TOKEN_FOR_ROLE_ROLE_ADMIN']
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        // Paso 4: Obtener las imágenes del hotel
        $client->request('GET', "/api/v1/hotel/image/{$hotelId}");
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('filename', $data[0]);
    }

    public function testGetHotelImagesReturnsNotFoundForInvalidHotel(): void
    {
        $client = static::createClient();

        // Usamos un ID muy alto que probablemente no exista
        $invalidHotelId = 999999;

        $client->request('GET', "/api/v1/hotel/image/{$invalidHotelId}");

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Hotel not found.', $data['message']);
    }
}
