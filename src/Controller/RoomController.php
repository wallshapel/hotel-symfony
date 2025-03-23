<?php

namespace App\Controller;

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
}
