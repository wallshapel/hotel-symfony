<?php

namespace App\Controller;

use App\Contract\HotelInterface;
use App\Security\Voter\RoleVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/hotel', name: 'api_v1_')]
class HotelController extends AbstractController
{

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(Request $request, HotelInterface $hotelService): JsonResponse
    {        
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        $result = $hotelService->create($data);

        return $this->json($result, $result['status']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, HotelInterface $hotelService): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $result = $hotelService->delete($id);
        return $this->json($result, $result['status']);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request, HotelInterface $hotelService): JsonResponse
    {
        if (!$this->isGranted(RoleVoter::ROLE_ADMIN)) {
            return $this->json(['message' => 'Access denied. Admins only.', 'status' => 403], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $hotelService->update($id, $data);

        return $this->json($result, $result['status']);
    }
}
