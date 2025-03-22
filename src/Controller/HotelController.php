<?php

namespace App\Controller;

use App\Service\HotelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
class HotelController extends AbstractController
{
    #[Route('/hotels', name: 'list', methods: ['get'])]
    public function list(HotelService $hotelService): JsonResponse
    {
        $hotels = $hotelService->getAll();
        return $this->json($hotels);
    }
}
