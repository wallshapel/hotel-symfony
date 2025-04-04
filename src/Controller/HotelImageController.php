<?php

namespace App\Controller;

use App\Contract\HotelImageInterface;
use App\Security\Voter\RoleVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/hotel/image', name: 'api_v1_hotel_image_')]
class HotelImageController extends AbstractController
{
    #[Route('/{id}/upload', name: 'upload', methods: ['POST'], defaults: ['_format' => null])]
    public function upload(
        int $id,
        Request $request,
        HotelImageInterface $imageUploadService
    ): JsonResponse {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $imageUploadService->uploadHotelImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/{id}/update', name: 'update', methods: ['POST'], defaults: ['_format' => null])]
    public function update(
        int $id,
        Request $request,
        HotelImageInterface $imageUploadService
    ): JsonResponse {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $imageUploadService->updateHotelImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id, HotelImageInterface $imageUploadService): JsonResponse
    {
        $result = $imageUploadService->getHotelImages($id);

        if (isset($result['status'])) {
            return $this->json($result, $result['status']);
        }

        return $this->json($result);
    }
}
