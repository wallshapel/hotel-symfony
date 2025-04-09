<?php

namespace App\Tests\Service;

use App\Entity\Hotel;
use App\Entity\Image;
use App\Entity\Room;
use App\Repository\HotelRepository;
use App\Service\ImageUploadService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class ImageUploadServiceTest extends TestCase
{
    private string $imageDir = '/tmp/uploads';
    private $slugger;
    private $hotelRepository;
    private $em;
    private ImageUploadService $service;

    protected function setUp(): void
    {
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->hotelRepository = $this->createMock(HotelRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new ImageUploadService(
            $this->imageDir,
            $this->slugger,
            $this->hotelRepository,
            $this->em
        );
    }

    public function testUploadHotelImageReturnsHotelNotFound(): void
    {
        $request = new Request();

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $result = $this->service->uploadHotelImage(99, $request);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Hotel not found.', $result['message']);
    }

    public function testUploadHotelImageReturnsNoImagesUploaded(): void
    {
        $request = new Request(); // No establecemos archivos

        $mockHotel = $this->createMock(Hotel::class);
        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockHotel);

        // No seteamos archivos manualmente

        $result = $this->service->uploadHotelImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No images uploaded.', $result['message']);
    }

    public function testUploadHotelImageIgnoresInvalidImage(): void
    {
        $mockHotel = $this->createMock(Hotel::class);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockHotel);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadHotelImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadHotelImageSkipsImageExceedingMaxSize(): void
    {
        $mockHotel = $this->createMock(Hotel::class);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockHotel);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(6 * 1024 * 1024); // 6MB

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadHotelImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadHotelImageSkipsUnsupportedMimeType(): void
    {
        $mockHotel = $this->createMock(Hotel::class);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockHotel);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(1024); // 1KB
        $file->expects($this->once())->method('getMimeType')->willReturn('application/pdf');

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadHotelImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadHotelImageSkipsFileExceptionOnMove(): void
    {
        $mockHotel = $this->createMock(Hotel::class);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockHotel);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(1024); // 1KB
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
        $file->expects($this->once())->method('getClientOriginalName')->willReturn('foto.jpg');
        $file->expects($this->once())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('move')->willThrowException(new FileException('Error'));

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('foto')
            ->willReturn(new UnicodeString('foto'));

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadHotelImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadHotelImageSuccess(): void
    {
        $mockHotel = $this->createMock(Hotel::class);

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockHotel);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/png');
        $file->method('getClientOriginalName')->willReturn('mi-foto.png');
        $file->expects($this->once())->method('guessExtension')->willReturn('png');
        $file->expects($this->once())->method('move');

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('mi-foto')
            ->willReturn(new UnicodeString('mi-foto'));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->uploadHotelImage(1, $request);

        $this->assertSame(201, $result['status']);
        $this->assertSame('Images uploaded successfully.', $result['message']);
        $this->assertIsArray($result['images']);
        $this->assertCount(1, $result['images']);
        $this->assertArrayHasKey('filename', $result['images'][0]);
        $this->assertArrayHasKey('originalName', $result['images'][0]);
        $this->assertArrayHasKey('url', $result['images'][0]);
    }

    public function testUploadRoomImageReturnsRoomNotFound(): void
    {
        $request = new Request();

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $result = $this->service->uploadRoomImage(99, $request);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Room not found.', $result['message']);
    }

    public function testUploadRoomImageReturnsNoImagesUploaded(): void
    {
        $mockRoom = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockRoom);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $request = new Request(); // sin archivos

        $result = $this->service->uploadRoomImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No images uploaded.', $result['message']);
    }

    public function testUploadRoomImageSkipsInvalidImage(): void
    {
        $mockRoom = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockRoom);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(false);

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadRoomImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadRoomImageSkipsImageExceedingMaxSize(): void
    {
        $mockRoom = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockRoom);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(6 * 1024 * 1024); // 6MB

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadRoomImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadRoomImageSkipsUnsupportedMimeType(): void
    {
        $mockRoom = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockRoom);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(1024); // 1KB
        $file->expects($this->once())->method('getMimeType')->willReturn('application/pdf');

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadRoomImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadRoomImageSkipsFileExceptionOnMove(): void
    {
        $mockRoom = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockRoom);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
        $file->expects($this->once())->method('getClientOriginalName')->willReturn('room-pic.jpg');
        $file->expects($this->once())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('move')->willThrowException(new FileException('move error'));

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('room-pic')
            ->willReturn(new UnicodeString('room-pic'));

        $request = new Request([], [], [], [], ['image' => $file]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->uploadRoomImage(1, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No valid images were uploaded.', $result['message']);
    }

    public function testUploadRoomImageSuccess(): void
    {
        $mockRoom = $this->createMock(Room::class);

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mockRoom);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/png');
        $file->method('getClientOriginalName')->willReturn('room-picture.png');
        $file->expects($this->once())->method('guessExtension')->willReturn('png');
        $file->expects($this->once())->method('move');

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('room-picture')
            ->willReturn(new UnicodeString('room-picture'));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->uploadRoomImage(1, $request);

        $this->assertSame(201, $result['status']);
        $this->assertSame('Images uploaded successfully.', $result['message']);
        $this->assertIsArray($result['images']);
        $this->assertCount(1, $result['images']);
        $this->assertArrayHasKey('filename', $result['images'][0]);
        $this->assertArrayHasKey('originalName', $result['images'][0]);
        $this->assertArrayHasKey('url', $result['images'][0]);
    }

    public function testUpdateHotelImageReturnsNoImageUploaded(): void
    {
        $request = new Request(); // sin archivos

        $result = $this->service->updateHotelImage(42, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No image uploaded.', $result['message']);
    }

    public function testUpdateHotelImageRejectsImageExceedingMaxSize(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(6 * 1024 * 1024); // 6MB

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateHotelImage(42, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('Image exceeds maximum allowed size (5MB).', $result['message']);
    }

    public function testUpdateHotelImageRejectsUnsupportedMimeType(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024); // tamaño válido
        $file->expects($this->once())->method('getMimeType')->willReturn('application/pdf'); // no permitido

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateHotelImage(42, $request);

        $this->assertSame(415, $result['status']);
        $this->assertSame('Unsupported image format.', $result['message']);
    }

    public function testUpdateHotelImageReturnsImageNotFound(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/png');

        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getHotel')->willReturn(null); // no asociado a hotel

        $imageRepo = $this->createMock(EntityRepository::class);
        $imageRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($image);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Image::class)
            ->willReturn($imageRepo);

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateHotelImage(42, $request);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Hotel image not found.', $result['message']);
    }

    public function testUpdateHotelImageFailsOnMoveException(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('hotel-new.jpg');
        $file->expects($this->once())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('move')->willThrowException(new FileException('move error'));

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('hotel-new')
            ->willReturn(new UnicodeString('hotel-new'));

        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getHotel')->willReturn($this->createMock(Hotel::class));
        $image->expects($this->once())->method('getFilename')->willReturn('old.jpg');

        $imageRepo = $this->createMock(EntityRepository::class);
        $imageRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($image);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Image::class)
            ->willReturn($imageRepo);

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateHotelImage(42, $request);

        $this->assertSame(500, $result['status']);
        $this->assertSame('Image upload failed.', $result['message']);
    }

    public function testUpdateHotelImageSuccess(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/png');
        $file->method('getClientOriginalName')->willReturn('nueva.png');
        $file->expects($this->once())->method('guessExtension')->willReturn('png');
        $file->expects($this->once())->method('move');

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('nueva')
            ->willReturn(new UnicodeString('nueva'));

        $hotel = $this->createMock(Hotel::class);

        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getHotel')->willReturn($hotel);
        $image->expects($this->once())->method('getFilename')->willReturn('vieja.png');
        $image->expects($this->once())->method('setFilename');
        $image->expects($this->once())->method('setOriginalName');

        // Simular que existe el archivo anterior para que entre al unlink()
        $oldPath = $this->imageDir . '/images/hotels/vieja.png';
        file_put_contents($oldPath, 'dummy'); // crear temporal

        $imageRepo = $this->createMock(EntityRepository::class);
        $imageRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($image);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Image::class)
            ->willReturn($imageRepo);

        $this->em->expects($this->once())->method('flush');

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateHotelImage(42, $request);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Hotel image updated successfully.', $result['message']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('url', $result);

        // limpiar archivo temporal
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    public function testUpdateRoomImageReturnsNoImageUploaded(): void
    {
        $request = new Request(); // sin archivos

        $result = $this->service->updateRoomImage(42, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('No image uploaded.', $result['message']);
    }

    public function testUpdateRoomImageRejectsImageExceedingMaxSize(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(6 * 1024 * 1024); // 6MB

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateRoomImage(42, $request);

        $this->assertSame(400, $result['status']);
        $this->assertSame('Image exceeds maximum allowed size (5MB).', $result['message']);
    }

    public function testUpdateRoomImageRejectsUnsupportedMimeType(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('application/pdf');

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateRoomImage(42, $request);

        $this->assertSame(415, $result['status']);
        $this->assertSame('Unsupported image format.', $result['message']);
    }

    public function testUpdateRoomImageReturnsImageNotFound(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');

        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getRoom')->willReturn(null); // imagen no vinculada a room

        $imageRepo = $this->createMock(EntityRepository::class);
        $imageRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($image);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Image::class)
            ->willReturn($imageRepo);

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateRoomImage(42, $request);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Room image not found.', $result['message']);
    }

    public function testUpdateRoomImageFailsOnMoveException(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/png');
        $file->method('getClientOriginalName')->willReturn('room-pic.png');
        $file->expects($this->once())->method('guessExtension')->willReturn('png');
        $file->expects($this->once())->method('move')->willThrowException(new FileException('move error'));

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('room-pic')
            ->willReturn(new UnicodeString('room-pic'));

        $room = $this->createMock(Room::class);

        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getRoom')->willReturn($room);
        $image->expects($this->once())->method('getFilename')->willReturn('old-room.png');

        $imageRepo = $this->createMock(EntityRepository::class);
        $imageRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($image);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Image::class)
            ->willReturn($imageRepo);

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateRoomImage(42, $request);

        $this->assertSame(500, $result['status']);
        $this->assertSame('Image upload failed.', $result['message']);
    }

    public function testUpdateRoomImageSuccess(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('nueva-room.jpg');
        $file->expects($this->once())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('move');

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('nueva-room')
            ->willReturn(new UnicodeString('nueva-room'));

        $room = $this->createMock(Room::class);

        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getRoom')->willReturn($room);
        $image->expects($this->once())->method('getFilename')->willReturn('vieja-room.jpg');
        $image->expects($this->once())->method('setFilename');
        $image->expects($this->once())->method('setOriginalName');

        // Crear archivo temporal simulado para eliminar
        $oldPath = $this->imageDir . '/images/rooms/vieja-room.jpg';
        if (!is_dir(dirname($oldPath))) {
            mkdir(dirname($oldPath), 0777, true);
        }
        file_put_contents($oldPath, 'dummy');

        $imageRepo = $this->createMock(EntityRepository::class);
        $imageRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($image);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Image::class)
            ->willReturn($imageRepo);

        $this->em->expects($this->once())->method('flush');

        $request = new Request([], [], [], [], ['image' => $file]);

        $result = $this->service->updateRoomImage(42, $request);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Room image updated successfully.', $result['message']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('url', $result);

        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    public function testGetHotelImagesReturnsHotelNotFound(): void
    {
        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $result = $this->service->getHotelImages(99);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Hotel not found.', $result['message']);
    }

    public function testGetHotelImagesReturnsImageData(): void
    {
        $image1 = $this->createMock(Image::class);
        $image1->method('getId')->willReturn(1);
        $image1->method('getFilename')->willReturn('image1.jpg');
        $image1->method('getOriginalName')->willReturn('original1.jpg');

        $image2 = $this->createMock(Image::class);
        $image2->method('getId')->willReturn(2);
        $image2->method('getFilename')->willReturn('image2.jpg');
        $image2->method('getOriginalName')->willReturn('original2.jpg');

        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getImages')->willReturn(new ArrayCollection([$image1, $image2]));

        $this->hotelRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($hotel);

        $result = $this->service->getHotelImages(1);

        $this->assertCount(2, $result);

        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('image1.jpg', $result[0]['filename']);
        $this->assertSame('original1.jpg', $result[0]['originalName']);
        $this->assertSame('/uploads/images/hotels/image1.jpg', $result[0]['url']);

        $this->assertSame(2, $result[1]['id']);
        $this->assertSame('image2.jpg', $result[1]['filename']);
        $this->assertSame('original2.jpg', $result[1]['originalName']);
        $this->assertSame('/uploads/images/hotels/image2.jpg', $result[1]['url']);
    }

    public function testGetRoomImagesReturnsRoomNotFound(): void
    {
        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $result = $this->service->getRoomImages(99);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Room not found.', $result['message']);
    }

    public function testGetRoomImagesReturnsImageData(): void
    {
        $image1 = $this->createMock(Image::class);
        $image1->method('getId')->willReturn(1);
        $image1->method('getFilename')->willReturn('room1.jpg');
        $image1->method('getOriginalName')->willReturn('original1.jpg');

        $image2 = $this->createMock(Image::class);
        $image2->method('getId')->willReturn(2);
        $image2->method('getFilename')->willReturn('room2.jpg');
        $image2->method('getOriginalName')->willReturn('original2.jpg');

        $room = $this->createMock(Room::class);
        $room->method('getImages')->willReturn(new ArrayCollection([$image1, $image2]));

        $roomRepo = $this->createMock(EntityRepository::class);
        $roomRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($room);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Room::class)
            ->willReturn($roomRepo);

        $result = $this->service->getRoomImages(1);

        $this->assertCount(2, $result);

        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('room1.jpg', $result[0]['filename']);
        $this->assertSame('original1.jpg', $result[0]['originalName']);
        $this->assertSame('/uploads/images/rooms/room1.jpg', $result[0]['url']);

        $this->assertSame(2, $result[1]['id']);
        $this->assertSame('room2.jpg', $result[1]['filename']);
        $this->assertSame('original2.jpg', $result[1]['originalName']);
        $this->assertSame('/uploads/images/rooms/room2.jpg', $result[1]['url']);
    }
}
