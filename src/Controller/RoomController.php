<?php

namespace App\Controller;

use App\Contract\RoomInterface;
use App\Security\Voter\RoleVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_rooms_')]
class RoomController extends AbstractController
{

    private RoomInterface $roomService;

    public function __construct(
        RoomInterface $roomService
    ) {
        $this->roomService = $roomService;
    }

    #[Route('/rooms/available', name: 'available_paginated', methods: ['GET'])]
    public function getAvailablePaginated(Request $request): JsonResponse
    {
        $filters = [
            'start_date' => $request->query->get('start_date'),
            'end_date' => $request->query->get('end_date'),
            'page' => $request->query->get('page', 1),
            'limit' => $request->query->get('limit', 10),
        ];

        $result = $this->roomService->getAvailableRoomsPaginated($filters);
        return $this->json($result, $result['status']);
    }

    #[Route('/room', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->roomService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/room/{id}', name: 'get_by_id', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $result = $this->roomService->getById($id);

        $status = $result['status_code'] ?? ($result['status'] ?? 200);
        unset($result['status_code']);

        return $this->json($result, $status);
    }

    #[Route('/rooms/{id}/images', name: 'room_images', methods: ['GET'])]
    public function getRoomImages(int $id): JsonResponse
    {
        $result = $this->roomService->getRoomImages($id);

        return isset($result['status'])
            ? $this->json($result, $result['status'])
            : $this->json($result);
    }

    #[Route('/room/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $this->roomService->delete($id);
        return $this->json($result, $result['status']);
    }

    #[Route('/room/{id}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->roomService->update($id, $data);

        return $this->json($result, $result['status']);
    }
}
