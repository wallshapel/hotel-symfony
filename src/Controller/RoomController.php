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
    #[Route('/room', name: 'create', methods: ['POST'])]
    public function create(Request $request, RoomService $roomService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $roomService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/room/{id}', name: 'get_by_id', methods: ['GET'])]
    public function getById(int $id, RoomService $roomService): JsonResponse
    {
        $result = $roomService->getById($id);

        $status = $result['status_code'] ?? ($result['status'] ?? 200);
        unset($result['status_code']);

        return $this->json($result, $status);
    }

    #[Route('/rooms/{id}/upload-image', name: 'upload_room_image', methods: ['POST'], defaults: ['_format' => null])]
    public function uploadRoomImage(int $id, Request $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $imageUploadService->uploadRoomImage($id, $request);

        return $this->json($result, $result['status']);
    }

    #[Route('/rooms/image/{id}/update', name: 'update_room_image', methods: ['POST'], defaults: ['_format' => null])]
    public function updateRoomImage(
        int $id,
        Request $request,
        ImageUploadService $imageUploadService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

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

    #[Route('/room/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, RoomService $roomService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $roomService->delete($id);
        return $this->json($result, $result['status']);
    }

    #[Route('/room/{id}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request, RoomService $roomService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $roomService->update($id, $data);

        return $this->json($result, $result['status']);
    }
}
