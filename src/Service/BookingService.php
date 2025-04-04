<?php

namespace App\Service;

use App\Contract\BookingInterface;
use App\Contract\HotelImageInterface;
use App\Entity\Booking;
use App\Entity\Room;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class BookingService implements BookingInterface
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private TokenStorageInterface $tokenStorage;
    private RoomService $roomService;
    private HotelImageInterface $hotelImageInterface;

    public function __construct(
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        RoomService $roomService,
        HotelImageInterface $hotelImageInterface
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->tokenStorage = $tokenStorage;
        $this->roomService = $roomService;
        $this->hotelImageInterface = $hotelImageInterface;
    }

    public function getAllReservationsPaginated(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, (int) ($filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder();
        $qb->select('b', 'r', 'h')
            ->from(Booking::class, 'b')
            ->join('b.room', 'r')
            ->join('r.hotel', 'h')
            ->orderBy('b.startDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $bookings = $qb->getQuery()->getResult();

        $totalQb = $this->em->createQueryBuilder();
        $totalQb->select('COUNT(b.id)')
            ->from(Booking::class, 'b');
        $total = (int) $totalQb->getQuery()->getSingleScalarResult();

        $result = [];

        foreach ($bookings as $booking) {
            $room = $booking->getRoom();
            $hotel = $room->getHotel();

            $roomImages = $this->roomService->getRoomImages($room->getId());
            $roomImages = isset($roomImages['status']) ? [] : $roomImages;

            $hotelImages = $this->hotelImageInterface->getHotelImages($hotel->getId());
            $hotelImages = isset($hotelImages['status']) ? [] : $hotelImages;

            $result[] = [
                'id' => $booking->getId(),
                'start_date' => $booking->getStartDate()->format('Y-m-d'),
                'end_date' => $booking->getEndDate()->format('Y-m-d'),
                'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
                'room' => [
                    'id' => $room->getId(),
                    'number' => $room->getNumber(),
                    'type' => $room->getType(),
                    'capacity' => $room->getCapacity(),
                    'price' => $room->getPrice(),
                    'status' => $room->getStatus(),
                    'images' => $roomImages
                ],
                'hotel' => [
                    'id' => $hotel->getId(),
                    'name' => $hotel->getName(),
                    'city' => $hotel->getCity(),
                    'country' => $hotel->getCountry(),
                    'images' => $hotelImages
                ]
            ];
        }

        return [
            'data' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'count' => count($result),
                'total' => $total,
                'totalPages' => ceil($total / $limit)
            ],
            'status' => JsonResponse::HTTP_OK
        ];
    }

    public function getPastBookingsPaginated(array $filters): array
    {
        $now = new \DateTime();

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, (int) ($filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder();
        $qb->select('b', 'r', 'h')
            ->from(Booking::class, 'b')
            ->join('b.room', 'r')
            ->join('r.hotel', 'h')
            ->where('b.endDate < :now')
            ->setParameter('now', $now)
            ->orderBy('b.endDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $bookings = $qb->getQuery()->getResult();

        $countQb = $this->em->createQueryBuilder();
        $countQb->select('COUNT(b.id)')
            ->from(Booking::class, 'b')
            ->where('b.endDate < :now')
            ->setParameter('now', $now);

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $data = [];
        foreach ($bookings as $booking) {
            $room = $booking->getRoom();
            $hotel = $room->getHotel();
            $roomImages = $this->roomService->getRoomImages($room->getId());
            $hotelImages = $this->hotelImageInterface->getHotelImages($hotel->getId());

            $data[] = [
                'id' => $booking->getId(),
                'start_date' => $booking->getStartDate()->format('Y-m-d'),
                'end_date' => $booking->getEndDate()->format('Y-m-d'),
                'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
                'room' => [
                    'id' => $room->getId(),
                    'number' => $room->getNumber(),
                    'type' => $room->getType(),
                    'capacity' => $room->getCapacity(),
                    'price' => $room->getPrice(),
                    'images' => $roomImages
                ],
                'hotel' => [
                    'id' => $hotel->getId(),
                    'name' => $hotel->getName(),
                    'city' => $hotel->getCity(),
                    'country' => $hotel->getCountry(),
                    'images' => $hotelImages
                ]
            ];
        }

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

    public function getCurrentBookingsPaginated(array $filters): array
    {
        $now = new \DateTimeImmutable();
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, (int)($filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder();
        $qb->select('b', 'r', 'h')
            ->from(Booking::class, 'b')
            ->join('b.room', 'r')
            ->join('r.hotel', 'h')
            ->where('b.startDate <= :now')
            ->andWhere('b.endDate >= :now')
            ->setParameter('now', $now)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $bookings = $qb->getQuery()->getResult();

        $countQb = $this->em->createQueryBuilder();
        $countQb->select('COUNT(b.id)')
            ->from(Booking::class, 'b')
            ->where('b.startDate <= :now')
            ->andWhere('b.endDate >= :now')
            ->setParameter('now', $now);

        $total = (int)$countQb->getQuery()->getSingleScalarResult();

        $data = array_map(function (Booking $booking) {
            $room = $booking->getRoom();
            $hotel = $room->getHotel();

            $roomImages = $room->getImages()->map(fn($img) => [
                'id' => $img->getId(),
                'filename' => $img->getFilename(),
                'originalName' => $img->getOriginalName(),
                'url' => '/uploads/images/rooms/' . $img->getFilename()
            ])->toArray();

            $hotelImages = $hotel->getImages()->map(fn($img) => [
                'id' => $img->getId(),
                'filename' => $img->getFilename(),
                'originalName' => $img->getOriginalName(),
                'url' => '/uploads/images/hotels/' . $img->getFilename()
            ])->toArray();

            return [
                'id' => $booking->getId(),
                'start_date' => $booking->getStartDate()->format('Y-m-d'),
                'end_date' => $booking->getEndDate()->format('Y-m-d'),
                'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
                'room' => [
                    'id' => $room->getId(),
                    'number' => $room->getNumber(),
                    'type' => $room->getType(),
                    'capacity' => $room->getCapacity(),
                    'price' => $room->getPrice(),
                    'images' => $roomImages
                ],
                'hotel' => [
                    'id' => $hotel->getId(),
                    'name' => $hotel->getName(),
                    'city' => $hotel->getCity(),
                    'country' => $hotel->getCountry(),
                    'images' => $hotelImages
                ],
            ];
        }, $bookings);

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

    public function getFutureBookingsPaginated(array $filters): array
    {
        $today = new \DateTimeImmutable();
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, (int)($filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder();
        $qb->select('b', 'r', 'h', 'u')
            ->from(Booking::class, 'b')
            ->join('b.room', 'r')
            ->join('r.hotel', 'h')
            ->join('b.user', 'u')
            ->where('b.startDate > :today')
            ->setParameter('today', $today)
            ->orderBy('b.startDate', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $bookings = $qb->getQuery()->getResult();

        $countQb = $this->em->createQueryBuilder();
        $countQb->select('COUNT(b.id)')
            ->from(Booking::class, 'b')
            ->where('b.startDate > :today')
            ->setParameter('today', $today);

        $total = (int)$countQb->getQuery()->getSingleScalarResult();

        $data = array_map(function (Booking $booking) {
            $room = $booking->getRoom();
            $hotel = $room->getHotel();

            $roomImages = $room->getImages()->map(function ($img) {
                return [
                    'id' => $img->getId(),
                    'filename' => $img->getFilename(),
                    'originalName' => $img->getOriginalName(),
                    'url' => '/uploads/images/rooms/' . $img->getFilename()
                ];
            })->toArray();

            $hotelImages = $hotel->getImages()->map(function ($img) {
                return [
                    'id' => $img->getId(),
                    'filename' => $img->getFilename(),
                    'originalName' => $img->getOriginalName(),
                    'url' => '/uploads/images/hotels/' . $img->getFilename()
                ];
            })->toArray();

            return [
                'id' => $booking->getId(),
                'start_date' => $booking->getStartDate()->format('Y-m-d'),
                'end_date' => $booking->getEndDate()->format('Y-m-d'),
                'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $booking->getUser()->getId(),
                    'username' => $booking->getUser()->getUserIdentifier()
                ],
                'room' => [
                    'id' => $room->getId(),
                    'number' => $room->getNumber(),
                    'type' => $room->getType(),
                    'capacity' => $room->getCapacity(),
                    'price' => $room->getPrice(),
                    'images' => $roomImages
                ],
                'hotel' => [
                    'id' => $hotel->getId(),
                    'name' => $hotel->getName(),
                    'city' => $hotel->getCity(),
                    'country' => $hotel->getCountry(),
                    'images' => $hotelImages
                ]
            ];
        }, $bookings);

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
