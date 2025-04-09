<?php

namespace App\Service;

use App\Contract\HotelImageInterface;
use App\Contract\RoomInterface;
use App\DataTransformer\RoomInputTransformer;
use App\Entity\Hotel;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RoomService implements RoomInterface
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private RoomRepository $roomRepository;
    private HotelImageInterface $hotelImageService;
    private RoomInputTransformer $transformer;

    public function __construct(
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        RoomRepository $roomRepository,
        HotelImageInterface $hotelImageService,
        RoomInputTransformer $transformer
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->roomRepository = $roomRepository;
        $this->hotelImageService = $hotelImageService;
        $this->transformer = $transformer;
    }

    public function getAvailableRoomsPaginated(array $filters): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            return [
                'message' => 'Start and end dates are required.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
        } catch (\Exception $e) {
            return [
                'message' => 'Invalid date format.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        if ($end <= $start) {
            return [
                'message' => 'End date must be after start date.',
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, (int)($filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(Room::class, 'r')
            ->leftJoin('r.bookings', 'b', 'WITH', '(b.startDate <= :endDate AND b.endDate >= :startDate)')
            ->where('b.id IS NULL')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end);

        $rooms = $qb->getQuery()->getResult();

        $countQb = $this->em->createQueryBuilder();
        $countQb->select('COUNT(r.id)')
            ->from(Room::class, 'r')
            ->leftJoin('r.bookings', 'b', 'WITH', '(b.startDate <= :endDate AND b.endDate >= :startDate)')
            ->where('b.id IS NULL')
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end);

        $total = (int)$countQb->getQuery()->getSingleScalarResult();

        $data = array_map(function (Room $room) {
            $roomImages = $this->getRoomImages($room->getId());
            $hotel = $room->getHotel();
            $hotelImages = $this->hotelImageService->getHotelImages($hotel->getId());

            return [
                'id' => $room->getId(),
                'number' => $room->getNumber(),
                'type' => $room->getType(),
                'capacity' => $room->getCapacity(),
                'price' => $room->getPrice(),
                'hotel' => [
                    'name' => $hotel->getName(),
                    'city' => $hotel->getCity(),
                    'country' => $hotel->getCountry(),
                    'images' => isset($hotelImages['status']) ? [] : $hotelImages
                ],
                'images' => isset($roomImages['status']) ? [] : $roomImages
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

    public function create(array $data): array
    {
        $hotel = $this->em->getRepository(Hotel::class)->find($data['hotel_id'] ?? 0);
        if (!$hotel) {
            return ['message' => 'Hotel not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        $room = $this->transformer->fromArray($data, $hotel);

        $errors = $this->validator->validate($room);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->persist($room);
        $this->em->flush();

        return ['message' => 'Room created successfully.', 'status' => JsonResponse::HTTP_CREATED];
    }

    public function getById(int $id): array
    {
        $room = $this->roomRepository->find($id);

        if (!$room) {
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        $roomImages = [];
        foreach ($room->getImages() as $image) {
            $roomImages[] = [
                'id' => $image->getId(),
                'filename' => $image->getFilename(),
                'originalName' => $image->getOriginalName(),
                'url' => '/uploads/images/rooms/' . $image->getFilename()
            ];
        }

        $hotel = $room->getHotel();
        $hotelImages = $this->hotelImageService->getHotelImages($hotel->getId());
        $hotelImages = isset($hotelImages['status']) ? [] : $hotelImages;

        return [
            'id' => $room->getId(),
            'number' => $room->getNumber(),
            'type' => $room->getType(),
            'capacity' => $room->getCapacity(),
            'price' => $room->getPrice(),
            'status' => $room->getStatus(),
            'hotel' => [
                'id' => $hotel->getId(),
                'name' => $hotel->getName(),
                'city' => $hotel->getCity(),
                'images' => $hotelImages
            ],
            'images' => $roomImages,
            'status_code' => JsonResponse::HTTP_OK
        ];
    }

    public function getRoomImages(int $roomId): array
    {
        $room = $this->em->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        $images = $room->getImages();
        $data = [];

        foreach ($images as $image) {
            $data[] = [
                'id' => $image->getId(),
                'filename' => $image->getFilename(),
                'originalName' => $image->getOriginalName(),
                'url' => '/uploads/images/rooms/' . $image->getFilename()
            ];
        }

        return $data;
    }

    public function delete(int $id): array
    {
        $room = $this->em->getRepository(Room::class)->find($id);

        if (!$room) {
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        $this->em->remove($room);
        $this->em->flush();

        return ['message' => 'Room deleted successfully.', 'status' => JsonResponse::HTTP_OK];
    }

    public function update(int $id, array $data): array
    {
        $room = $this->em->getRepository(Room::class)->find($id);
        if (!$room) {
            return ['message' => 'Room not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        $room = $this->transformer->updateFromArray($room, $data);

        $errors = $this->validator->validate($room);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->flush();

        return ['message' => 'Room updated successfully.', 'status' => JsonResponse::HTTP_OK];
    }
}
