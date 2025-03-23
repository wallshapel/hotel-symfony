<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class HotelService
{
    private EntityManagerInterface $em;
    private HotelRepository $hotelRepository;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, HotelRepository $hotelRepository, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->hotelRepository = $hotelRepository;
        $this->validator = $validator;
    }

    public function getAll(): array
    {
        $hotels = $this->hotelRepository->findAll();

        return array_map(fn(Hotel $hotel) => [
            'id' => $hotel->getId(),
            'name' => $hotel->getName(),
            'address' => $hotel->getAddress(),
            'city' => $hotel->getCity(),
            'country' => $hotel->getCountry(),
            'description' => $hotel->getDescription(),
            'createdAt' => $hotel->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $hotels);
    }

    public function create(array $data): array
    {
        if (!is_array($data))
            return ['message' => 'Invalid JSON format.', 'status' => JsonResponse::HTTP_BAD_REQUEST];

        $hotel = new Hotel();
        $hotel->setName($data['name'] ?? '');
        $hotel->setAddress($data['address'] ?? '');
        $hotel->setCity($data['city'] ?? '');
        $hotel->setCountry($data['country'] ?? '');
        $hotel->setDescription($data['description'] ?? '');
        $hotel->setCreatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($hotel);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error)
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->persist($hotel);
        $this->em->flush();

        return ['message' => 'Hotel created successfully.', 'status' => JsonResponse::HTTP_CREATED];
    }
}
