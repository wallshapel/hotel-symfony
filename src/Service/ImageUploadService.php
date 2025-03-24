<?php

namespace App\Service;

use App\Entity\Image;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        if (!$hotel) {
            return ['message' => 'Hotel not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('image');
        if (!$file) {
            return ['message' => 'No image uploaded.', 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            if (!file_exists($this->imageDir)) {
                mkdir($this->imageDir, 0777, true);
            }
            $file->move($this->imageDir, $newFilename);
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
            'url' => '/uploads/' . $newFilename,
            'status' => JsonResponse::HTTP_CREATED
        ];
    }
}
