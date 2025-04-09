<?php

namespace App\Service;

use App\Contract\HotelInterface;
use App\DataTransformer\HotelInputTransformer;
use App\Entity\Hotel;
use App\Normalizer\ValidationErrorNormalizer;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HotelService implements HotelInterface
{
    private EntityManagerInterface $em;
    private HotelRepository $hotelRepository;
    private ValidatorInterface $validator;
    private HotelInputTransformer $transformer;
    private ValidationErrorNormalizer $errorNormalizer;

    public function __construct(
        EntityManagerInterface $em, 
        HotelRepository $hotelRepository, 
        ValidatorInterface $validator,
        HotelInputTransformer $transformer,
        ValidationErrorNormalizer $errorNormalizer
        )
    {
        $this->em = $em;
        $this->hotelRepository = $hotelRepository;
        $this->validator = $validator;
        $this->transformer = $transformer;
        $this->errorNormalizer = $errorNormalizer;
    }

    public function create(array $data): array
    {
        if (!is_array($data)) {
            return ['message' => 'Invalid JSON format.', 'status' => JsonResponse::HTTP_BAD_REQUEST];
        }

        $hotel = $this->transformer->fromArray($data);

        $errors = $this->validator->validate($hotel);
        if (count($errors) > 0) {
            return [
                'errors' => $this->errorNormalizer->normalize($errors),
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        try {
            $this->em->persist($hotel);
            $this->em->flush();
        } catch (\Throwable $e) {
            dd(get_class($e), $e->getMessage(), $e->getTraceAsString());
        }
        

        return ['message' => 'Hotel created successfully.', 'status' => JsonResponse::HTTP_CREATED];
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

        $hotel = $this->transformer->updateFromArray($hotel, $data);

        $errors = $this->validator->validate($hotel);
        if (count($errors) > 0) {
            return [
                'errors' => $this->errorNormalizer->normalize($errors),
                'status' => JsonResponse::HTTP_BAD_REQUEST
            ];
        }

        $this->em->flush();

        return ['message' => 'Hotel updated successfully.', 'status' => JsonResponse::HTTP_OK];
    }
}
