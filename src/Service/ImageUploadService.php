<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Room;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploadService
{
    private string $imageDir;
    private SluggerInterface $slugger;
    private HotelRepository $hotelRepository;
    private EntityManagerInterface $em;

    public function __construct(
        string $imageDir,
        SluggerInterface $slugger,
        HotelRepository $hotelRepository,
        EntityManagerInterface $em
    ) {
        $this->imageDir = $imageDir;
        $this->slugger = $slugger;
        $this->hotelRepository = $hotelRepository;
        $this->em = $em;
    }

    public function uploadHotelImage(int $hotelId, Request $request): array
    {
        $hotel = $this->hotelRepository->find($hotelId);
        if (!$hotel)
            return ['message' => 'Hotel not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];

        $file = $request->files->get('image');
        if (!$file)
            return ['message' => 'No image uploaded.', 'status' => JsonResponse::HTTP_BAD_REQUEST];

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $directory = $this->imageDir . '/images/hotels';
        try {
            if (!file_exists($directory))
                mkdir($directory, 0777, true);
            $file->move($directory, $newFilename);
        } catch (FileException $e) {
            return ['message' => 'Image upload failed.', 'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
        }

        $image = new Image();
        $image->setFilename($newFilename);
        $image->setOriginalName($file->getClientOriginalName());
        $image->setHotel($hotel);

        $this->em->persist($image);
        $this->em->flush();

        return [
            'message' => 'Image uploaded successfully.',
            'filename' => $newFilename,
            'url' => '/uploads/images/hotels/' . $newFilename,
            'status' => JsonResponse::HTTP_CREATED
        ];
    }

    public function uploadRoomImage(int $roomId, Request $request): array
    {
        $room = $this->em->getRepository(Room::class)->find($roomId);
        if (!$room)
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];

        $file = $request->files->get('image');
        if (!$file)
            return ['message' => 'No image uploaded.', 'status' => JsonResponse::HTTP_BAD_REQUEST];

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $directory = $this->imageDir . '/images/rooms';
        try {
            if (!file_exists($directory))
                mkdir($directory, 0777, true);
            $file->move($directory, $newFilename);
        } catch (FileException $e) {
            return ['message' => 'Image upload failed.', 'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
        }

        $image = new Image();
        $image->setFilename($newFilename);
        $image->setOriginalName($file->getClientOriginalName());
        $image->setRoom($room);

        $this->em->persist($image);
        $this->em->flush();

        return [
            'message' => 'Image uploaded successfully.',
            'filename' => $newFilename,
            'url' => '/uploads/images/rooms/' . $newFilename,
            'status' => JsonResponse::HTTP_CREATED
        ];
    }

    public function updateHotelImage(int $imageId, Request $request): array
    {
        $file = $request->files->get('image');
        if (!$file) {
            return [
                'message' => 'No image uploaded.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        $image = $this->em->getRepository(Image::class)->find($imageId);
        if (!$image || !$image->getHotel()) {
            return [
                'message' => 'Hotel image not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
        }

        $previousPath = $this->imageDir . '/images/hotels/' . $image->getFilename();
        if (file_exists($previousPath))
            unlink($previousPath);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        $destination = $this->imageDir . '/images/hotels/';

        try {
            if (!file_exists($destination))
                mkdir($destination, 0777, true);

            $file->move($destination, $newFilename);
        } catch (FileException $e) {
            return [
                'message' => 'Image upload failed.',
                'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }

        $image->setFilename($newFilename);
        $image->setOriginalName($file->getClientOriginalName());

        $this->em->flush();

        return [
            'message' => 'Hotel image updated successfully.',
            'filename' => $newFilename,
            'url' => '/uploads/images/hotels/' . $newFilename,
            'status' => JsonResponse::HTTP_OK
        ];
    }

    public function updateRoomImage(int $imageId, Request $request): array
    {
        $file = $request->files->get('image');
        if (!$file) {
            return [
                'message' => 'No image uploaded.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        $image = $this->em->getRepository(Image::class)->find($imageId);
        if (!$image || !$image->getRoom()) {
            return [
                'message' => 'Room image not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
        }

        $previousPath = $this->imageDir . '/images/rooms/' . $image->getFilename();
        if (file_exists($previousPath))
            unlink($previousPath);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        $destination = $this->imageDir . '/images/rooms/';

        try {
            if (!file_exists($destination))
                mkdir($destination, 0777, true);

            $file->move($destination, $newFilename);
        } catch (FileException $e) {
            return [
                'message' => 'Image upload failed.',
                'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }

        $image->setFilename($newFilename);
        $image->setOriginalName($file->getClientOriginalName());

        $this->em->flush();

        return [
            'message' => 'Room image updated successfully.',
            'filename' => $newFilename,
            'url' => '/uploads/images/rooms/' . $newFilename,
            'status' => JsonResponse::HTTP_OK
        ];
    }
}
