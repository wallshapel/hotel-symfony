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
    private HotelService $hotelService;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, RoomRepository $roomRepository, HotelService $hotelService)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->roomRepository = $roomRepository;
        $this->hotelService = $hotelService;
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

    public function getById(int $id): array
    {
        $room = $this->roomRepository->find($id);

        if (!$room) {
            return [
                'message' => 'Room not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
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
        $hotelImages = $this->hotelService->getHotelImages($hotel->getId());
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

    public function delete(int $id): array
    {
        $room = $this->em->getRepository(Room::class)->find($id);

        if (!$room) {
            return [
                'message' => 'Room not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
        }

        $this->em->remove($room);
        $this->em->flush();

        return [
            'message' => 'Room deleted successfully.',
            'status' => JsonResponse::HTTP_OK
        ];
    }

    public function update(int $id, array $data): array
    {
        $room = $this->em->getRepository(Room::class)->find($id);

        if (!$room) {
            return [
                'message' => 'Room not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
        }

        if (isset($data['number'])) {
            $room->setNumber($data['number']);
        }

        if (isset($data['type'])) {
            $room->setType($data['type']);
        }

        if (isset($data['capacity'])) {
            $room->setCapacity((int) $data['capacity']);
        }

        if (isset($data['price'])) {
            $room->setPrice((float) $data['price']);
        }

        if (isset($data['status'])) {
            $room->setStatus($data['status']);
        }

        $errors = $this->validator->validate($room);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->flush();

        return [
            'message' => 'Room updated successfully.',
            'status' => JsonResponse::HTTP_OK
        ];
    }
}
