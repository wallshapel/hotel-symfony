<?php

namespace App\Controller;

use App\Service\ImageUploadService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_rooms_')]
class RoomController extends AbstractController
{
    #[Route('/room', name: 'create', methods: ['post'])]
    public function create(Request $request, RoomService $roomService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        $result = $roomService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/rooms', name: 'list', methods: ['get'])]
    public function list(RoomService $roomService): JsonResponse
    {
        $rooms = $roomService->getAll();
        return $this->json($rooms);
    }

    #[Route('/rooms/{id}/upload-image', name: 'upload_room_image', methods: ['POST'], defaults: ['_format' => null])]
    public function uploadRoomImage(int $id, Request $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $imageUploadService->uploadRoomImage($id, $request);

        return $this->json($result, $result['status']);
    }

    #[Route('/rooms/image/{id}/update', name: 'update_room_image', methods: ['POST'], defaults: ['_format' => null])]
    public function updateRoomImage(
        int $id,
        Request $request,
        ImageUploadService $imageUploadService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $imageUploadService->updateRoomImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/rooms/{id}/images', name: 'room_images', methods: ['GET'])]
    public function getRoomImages(int $id, RoomService $roomService): JsonResponse
    {
        $result = $roomService->getRoomImages($id);

        return isset($result['status'])
            ? $this->json($result, $result['status'])
            : $this->json($result);
    }
}
