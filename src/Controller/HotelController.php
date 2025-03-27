<?php

namespace App\Controller;

use App\Service\HotelService;
use App\Service\ImageUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
class HotelController extends AbstractController
{
    #[Route('/hotels', name: 'list', methods: ['GET'])]
    public function list(HotelService $hotelService): JsonResponse
    {
        $hotels = $hotelService->getAll();
        return $this->json($hotels);
    }

    #[Route('/hotel', name: 'create', methods: ['POST'])]
    public function create(Request $request, HotelService $hotelService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }
        $data = json_decode($request->getContent(), true);
        $result = $hotelService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/hotel/{id}/upload-image', name: 'upload_image', methods: ['POST'], defaults: ['_format' => null])]
    public function uploadImage(
        int $id,
        Request $request,
        ImageUploadService $imageUploadService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $imageUploadService->uploadHotelImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/hotels/{id}/images', name: 'hotel_images', methods: ['GET'])]
    public function getHotelImages(int $id, HotelService $hotelService): JsonResponse
    {
        $result = $hotelService->getHotelImages($id);

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
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $imageUploadService->updateHotelImage($id, $request);

        return $this->json($result, $result['status']);
    }

    #[Route('/hotel/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, HotelService $hotelService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $hotelService->delete($id);
        return $this->json($result, $result['status']);
    }

    #[Route('/hotel/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request, HotelService $hotelService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $hotelService->update($id, $data);

        return $this->json($result, $result['status']);
    }
}
