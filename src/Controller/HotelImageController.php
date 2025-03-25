<?php

namespace App\Controller;

use App\Service\ImageUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
class HotelImageController extends AbstractController
{
    #[Route('/hotel/{id}/upload-image', name: 'upload_image', methods: ['POST'], defaults: ['_format' => null])]
    public function uploadImage(
        int $id,
        Request $request,
        ImageUploadService $imageUploadService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $imageUploadService->uploadHotelImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/hotels/{id}/images', name: 'hotel_images', methods: ['GET'])]
    public function getHotelImages(int $id, ImageUploadService $imageUploadService): JsonResponse
    {
        $result = $imageUploadService->getHotelImages($id);

        if (isset($result['status']))
            return $this->json($result, $result['status']);

        return $this->json($result);
    }

    #[Route('/hotel/image/{id}/update', name: 'update_hotel_image', methods: ['POST'], defaults: ['_format' => null])]
    public function updateHotelImage(
        int $id,
        Request $request,
        ImageUploadService $imageUploadService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $imageUploadService->updateHotelImage($id, $request);

        return $this->json($result, $result['status']);
    }
}
