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
        $this->denyAccessUnlessGranted('ROLE_USER'); // o ROLE_ADMIN también si lo ves necesario

        $data = json_decode($request->getContent(), true);
        $result = $bookingService->create($data);

        return $this->json($result, $result['status']);
    }
}
