<?php

namespace App\Controller;

use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_booking_')]
class BookingController extends AbstractController
{
    #[Route('/booking', name: 'create', methods: ['POST'])]
    public function create(Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Users only.', 'status' => 403], 403);
        }
        $data = json_decode($request->getContent(), true);
        $result = $bookingService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/bookings', name: 'available_rooms', methods: ['GET'])]
    public function getAvailableRooms(Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Users only.', 'status' => 403], 403);
        }

        $filters = [
            'start_date' => $request->query->get('start_date'),
            'end_date' => $request->query->get('end_date'),
            'page' => $request->query->get('page', 1),
            'limit' => $request->query->get('limit', 10),
        ];

        $result = $bookingService->getAvailableRoomsPaginated($filters);
        return $this->json($result, $result['status']);
    }


    #[Route('/booking/{id}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Users only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $bookingService->update($id, $data);

        return $this->json($result, $result['status']);
    }

    #[Route('/booking/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Users only.', 'status' => 403], 403);
        }

        $result = $bookingService->delete($id);
        return $this->json($result, $result['status']);
    }
}
