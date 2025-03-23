<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RoomService
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
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
}
