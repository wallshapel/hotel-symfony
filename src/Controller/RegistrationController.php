<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface as ValidatorValidatorInterface;

#[Route('/api/v1', name: 'api_v1_')]
class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: 'post')]
    public function register(
        ManagerRegistry $doctrine,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorValidatorInterface $validator
    ): JsonResponse {
        $em = $doctrine->getManager();

        $data = json_decode($request->getContent(), true);

        if (!is_array($data))
            return $this->json([
                'message' => 'Invalid JSON format.'
            ], JsonResponse::HTTP_BAD_REQUEST);

        $email = $data['email'] ?? '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // Check if user with same email already exists
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser)
            return $this->json([
                'message' => 'A user with this email already exists.'
            ], JsonResponse::HTTP_CONFLICT);

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($password);

        // Validar con Symfony Validator
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error)
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            return $this->json(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Registered successfully'], JsonResponse::HTTP_CREATED);
    }
}
