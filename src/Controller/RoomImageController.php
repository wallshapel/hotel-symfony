<?php

namespace App\Controller;

use App\Contract\RoomImageInterface;
use App\Security\Voter\RoleVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/room/image', name: 'api_v1_room_image_')]
class RoomImageController extends AbstractController
{
    private RoomImageInterface $imageUploadService;

    public function __construct(RoomImageInterface $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    #[Route('/{id}/upload', name: 'upload', methods: ['POST'], defaults: ['_format' => null])]
    public function upload(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
        return $this->json([
            'message' => 'Access denied. Admins only.',
            'status' => 403
        ], 403);
    }

        $result = $this->imageUploadService->uploadRoomImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/{id}/update', name: 'update', methods: ['POST'], defaults: ['_format' => null])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
        return $this->json([
            'message' => 'Access denied. Admins only.',
            'status' => 403
        ], 403);
    }

        $result = $this->imageUploadService->updateRoomImage($id, $request);
        return $this->json($result, $result['status']);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $result = $this->imageUploadService->getRoomImages($id);

        return isset($result['status'])
            ? $this->json($result, $result['status'])
            : $this->json($result);
    }
}
