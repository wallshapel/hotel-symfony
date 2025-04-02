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

    public function getHotelImages(int $hotelId): array
    {
        $hotel = $this->hotelRepository->find($hotelId);
        if (!$hotel) {
            return [
                'message' => 'Hotel not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
        }

        $images = $hotel->getImages();
        $data = [];

        foreach ($images as $image) {
            $data[] = [
                'id' => $image->getId(),
                'filename' => $image->getFilename(),
                'originalName' => $image->getOriginalName(),
                'url' => '/uploads/images/hotels/' . $image->getFilename()
            ];
        }

        return $data;
    }

    public function delete(int $id): array
    {
        $hotel = $this->em->getRepository(Hotel::class)->find($id);

        if (!$hotel) {
            return [
                'message' => 'Hotel not found.',
                'status' => JsonResponse::HTTP_NOT_FOUND
            ];
        }

        $this->em->remove($hotel);
        $this->em->flush();

        return [
            'message' => 'Hotel deleted successfully.',
            'status' => JsonResponse::HTTP_OK
        ];
    }

    public function update(int $id, array $data): array
    {
        $hotel = $this->hotelRepository->find($id);

        if (!$hotel) {
            return ['message' => 'Hotel not found.', 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        if (isset($data['name'])) $hotel->setName($data['name']);
        if (isset($data['address'])) $hotel->setAddress($data['address']);
        if (isset($data['city'])) $hotel->setCity($data['city']);
        if (isset($data['country'])) $hotel->setCountry($data['country']);
        if (isset($data['description'])) $hotel->setDescription($data['description']);

        $errors = $this->validator->validate($hotel);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return ['errors' => $errorMessages, 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $this->em->flush();

        return ['message' => 'Hotel updated successfully.', 'status' => JsonResponse::HTTP_OK];
    }
}
