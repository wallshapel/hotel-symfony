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

    #[Route('/bookings/all', name: 'admin_list_all', methods: ['GET'])]
    public function listAll(Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $filters = [
            'page' => $request->query->get('page', 1),
            'limit' => $request->query->get('limit', 10)
        ];

        $result = $bookingService->getAllReservationsPaginated($filters);

        return $this->json($result, $result['status']);
    }

    #[Route('/bookings/past', name: 'past_bookings', methods: ['GET'])]
    public function getPastBookings(Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json([
                'message' => 'Access denied. Admins only.',
                'status' => 403
            ], 403);
        }

        $filters = $request->query->all();
        $result = $bookingService->getPastBookingsPaginated($filters);

        return $this->json($result, $result['status']);
    }

    #[Route('/bookings/current', name: 'current', methods: ['GET'])]
    public function current(Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $filters = $request->query->all();
        $result = $bookingService->getCurrentBookingsPaginated($filters);

        return $this->json($result, $result['status']);
    }

    #[Route('/bookings/future', name: 'future_bookings', methods: ['GET'])]
    public function getFutureBookings(Request $request, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $filters = $request->query->all();
        $result = $bookingService->getFutureBookingsPaginated($filters);

        return $this->json($result, $result['status']);
    }

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
