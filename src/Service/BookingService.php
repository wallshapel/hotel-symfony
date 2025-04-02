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

        if (!$user)
            return ['message' => 'Unauthorized', 'status' => JsonResponse::HTTP_UNAUTHORIZED];

        $room = $this->em->getRepository(Room::class)->find($data['room_id'] ?? null);
        if (!$room)
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];

        try {
            $startDate = new \DateTime($data['start_date'] ?? '');
            $endDate = new \DateTime($data['end_date'] ?? '');
        } catch (\Exception $e) {
            return ['message' => 'Invalid date format.', 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $conflictingBooking = $this->em->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->andWhere('b.room = :room')
            ->andWhere('
            (:startDate BETWEEN b.startDate AND b.endDate) OR
            (:endDate BETWEEN b.startDate AND b.endDate) OR
            (b.startDate BETWEEN :startDate AND :endDate)
        ')
            ->setParameter('room', $room)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getOneOrNullResult();

        if ($conflictingBooking) {
            return [
                'message' => 'This room is already booked during the selected period.',
                'status' => JsonResponse::HTTP_CONFLICT
            ];
        }

        $booking = new Booking();
        $booking->setUser($user);
        $booking->setRoom($room);
        $booking->setStartDate($startDate);
        $booking->setEndDate($endDate);
        $booking->setStatus('pending');
        $booking->setCreatedAt(new \DateTimeImmutable());
        $room->setStatus('reserved');

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

    public function getAvailableRoomsPaginated(array $filters): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!$user || !$user instanceof \App\Entity\User) {
            return [
                'message' => 'Unauthorized',
                'status' => JsonResponse::HTTP_UNAUTHORIZED
            ];
        }

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            return [
                'message' => 'Start and end dates are required.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        try {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
        } catch (\Exception $e) {
            return [
                'message' => 'Invalid date format.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        if ($endDate <= $startDate) {
            return [
                'message' => 'End date must be after start date.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, (int) ($filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        // Query para obtener habitaciones disponibles paginadas
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(Room::class, 'r')
            ->leftJoin('r.bookings', 'b', 'WITH', '
            (b.startDate <= :endDate AND b.endDate >= :startDate)
        ')
            ->where('b.id IS NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $rooms = $qb->getQuery()->getResult();

        // Query para obtener total sin paginación
        $countQb = $this->em->createQueryBuilder();
        $countQb->select('COUNT(r.id)')
            ->from(Room::class, 'r')
            ->leftJoin('r.bookings', 'b', 'WITH', '
            (b.startDate <= :endDate AND b.endDate >= :startDate)
        ')
            ->where('b.id IS NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $data = array_map(function (Room $room) {
            $hotel = $room->getHotel();

            return [
                'id' => $room->getId(),
                'number' => $room->getNumber(),
                'type' => $room->getType(),
                'capacity' => $room->getCapacity(),
                'price' => $room->getPrice(),
                'status' => $room->getStatus(),
                'hotel' => [
                    'name' => $hotel->getName(),
                    'city' => $hotel->getCity(),
                    'country' => $hotel->getCountry(),
                ]
            ];
        }, $rooms);

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'count' => count($data),
                'total' => $total,
                'totalPages' => ceil($total / $limit)
            ],
            'status' => JsonResponse::HTTP_OK
        ];
    }

    public function update(int $bookingId, array $data): array
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user || !$user instanceof \App\Entity\User)
            return ['message' => 'Unauthorized', 'status' => JsonResponse::HTTP_UNAUTHORIZED];

        if (!$user)
            return ['message' => 'Unauthorized', 'status' => JsonResponse::HTTP_UNAUTHORIZED];

        $booking = $this->em->getRepository(Booking::class)->find($bookingId);
        if (!$booking) {
            return ['message' => 'Booking not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            if ($booking->getUser()->getId() !== $user->getId()) {
                return ['message' => 'Forbidden. You can only modify your own bookings.', 'status' => JsonResponse::HTTP_FORBIDDEN];
            }
        }

        try {
            $startDate = new \DateTime($data['start_date'] ?? '');
            $endDate = new \DateTime($data['end_date'] ?? '');
        } catch (\Exception $e) {
            return ['message' => 'Invalid date format.', 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $room = $booking->getRoom();

        $conflictingBooking = $this->em->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->andWhere('b.room = :room')
            ->andWhere('b.id != :currentBooking')
            ->andWhere('
            (:startDate BETWEEN b.startDate AND b.endDate) OR
            (:endDate BETWEEN b.startDate AND b.endDate) OR
            (b.startDate BETWEEN :startDate AND :endDate)
        ')
            ->setParameter('room', $room)
            ->setParameter('currentBooking', $booking->getId())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getOneOrNullResult();

        if ($conflictingBooking) {
            return [
                'message' => 'This room is already booked during the selected period.',
                'status' => JsonResponse::HTTP_CONFLICT
            ];
        }

        $booking->setStartDate($startDate);
        $booking->setEndDate($endDate);
        $booking->setStatus('pending');
        $room->setStatus('reserved');

        $errors = $this->validator->validate($booking);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error)
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->flush();

        return ['message' => 'Booking updated successfully.', 'status' => JsonResponse::HTTP_OK];
    }

    public function delete(int $bookingId): array
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user || !$user instanceof \App\Entity\User) {
            return ['message' => 'Unauthorized', 'status' => JsonResponse::HTTP_UNAUTHORIZED];
        }

        $booking = $this->em->getRepository(Booking::class)->find($bookingId);

        if (!$booking) {
            return ['message' => 'Booking not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            if ($booking->getUser()->getId() !== $user->getId()) {
                return ['message' => 'Forbidden. You can only delete your own bookings.', 'status' => JsonResponse::HTTP_FORBIDDEN];
            }
        }

        $room = $booking->getRoom();
        $room->setStatus('available');

        $this->em->remove($booking);
        $this->em->flush();

        return ['message' => 'Booking deleted successfully.', 'status' => JsonResponse::HTTP_OK];
    }
}
