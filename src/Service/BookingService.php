<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingService
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->tokenStorage = $tokenStorage;
    }

    public function create(array $data): array
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user) {
            return ['message' => 'Unauthorized', 'status' => JsonResponse::HTTP_UNAUTHORIZED];
        }

        $room = $this->em->getRepository(Room::class)->find($data['room_id'] ?? null);
        if (!$room) {
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        try {
            $startDate = new \DateTime($data['start_date'] ?? '');
            $endDate = new \DateTime($data['end_date'] ?? '');
        } catch (\Exception $e) {
            return ['message' => 'Invalid date format.', 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $booking = new Booking();
        $booking->setUser($user);
        $booking->setRoom($room);
        $booking->setStartDate($startDate);
        $booking->setEndDate($endDate);
        $booking->setStatus('pending');
        $booking->setCreatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($booking);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error)
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->persist($booking);
        $this->em->flush();

        return ['message' => 'Booking created successfully.', 'status' => JsonResponse::HTTP_CREATED];
    }
}
