<?php

namespace App\Controller;

use App\Service\ImageUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
class RoomImageController extends AbstractController
{
    #[Route('/rooms/{id}/images', name: 'room_images', methods: ['GET'])]
    public function getRoomImages(int $id, ImageUploadService $imageUploadService): JsonResponse
    {
        $result = $imageUploadService->getRoomImages($id);

        return isset($result['status'])
            ? $this->json($result, $result['status'])
            : $this->json($result);
    }

    #[Route('/rooms/{id}/upload-image', name: 'upload_room_image', methods: ['POST'], defaults: ['_format' => null])]
    public function uploadRoomImage(int $id, Request $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $imageUploadService->uploadRoomImage($id, $request);

        return $this->json($result, $result['status']);
    }
}
