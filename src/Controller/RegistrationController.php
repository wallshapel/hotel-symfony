<?php

namespace App\Controller;

use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: 'post')]
    public function register(
        Request $request,
        UserRegistrationService $registrationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $result = $registrationService->registerUser($data);
        return $this->json($result, $result['status']);
    }
}
