<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RoomService
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private RoomRepository $roomRepository;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, RoomRepository $roomRepository)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->roomRepository = $roomRepository;
    }

    public function create(array $data): array
    {
        if (!is_array($data))
            return ['message' => 'Invalid JSON format.', 'status' => JsonResponse::HTTP_BAD_REQUEST];

        $hotel = $this->em->getRepository(Hotel::class)->find($data['hotel_id'] ?? 0);
        if (!$hotel)
            return ['message' => 'Hotel not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];

        $room = new Room();
        $room->setNumber($data['number'] ?? '');
        $room->setType($data['type'] ?? '');
        $room->setCapacity((int) ($data['capacity'] ?? 0));
        $room->setPrice((float) ($data['price'] ?? 0));
        $room->setStatus($data['status'] ?? '');
        $room->setHotel($hotel);

        $errors = $this->validator->validate($room);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error)
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();

            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->persist($room);
        $this->em->flush();

        return ['message' => 'Room created successfully.', 'status' => JsonResponse::HTTP_CREATED];
    }

    public function getAll(): array
    {
        $rooms = $this->roomRepository->findAll();

        return array_map(function ($room) {
            return [
                'id' => $room->getId(),
                'number' => $room->getNumber(),
                'type' => $room->getType(),
                'capacity' => $room->getCapacity(),
                'price' => $room->getPrice(),
                'status' => $room->getStatus(),
                'hotel' => [
                    'id' => $room->getHotel()->getId(),
                    'name' => $room->getHotel()->getName(),
                    'city' => $room->getHotel()->getCity(),
                ],
            ];
        }, $rooms);
    }

    public function getRoomImages(int $roomId): array
    {
        $room = $this->em->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return [
                'message' => 'Room not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
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
}
