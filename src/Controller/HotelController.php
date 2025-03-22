<?php

namespace App\Controller;

use App\Service\HotelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/hotel', name: 'create', methods: ['post'])]
    public function create(Request $request, HotelService $hotelService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        $result = $hotelService->create($data);

        return $this->json($result, $result['status']);
    }
}
