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
    #[Route('/booking', name: 'create', methods: ['post'])]
    public function create(Request $request, BookingService $bookingService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $data = json_decode($request->getContent(), true);
        $result = $bookingService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/bookings', name: 'list', methods: ['get'])]
    public function list(BookingService $bookingService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $result = $bookingService->getUserBookings();
        return $this->json($result, $result['status']);
    }

    #[Route('/booking/{id}', name: 'update', methods: ['patch'])]
    public function update(int $id, Request $request, BookingService $bookingService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $result = $bookingService->update($id, $data);

        return $this->json($result, $result['status']);
    }

    #[Route('/booking/{id}', name: 'delete', methods: ['delete'])]
    public function delete(int $id, BookingService $bookingService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $result = $bookingService->delete($id);
        return $this->json($result, $result['status']);
    }
}
