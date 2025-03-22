<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Repository\HotelRepository;

class HotelService
{
    private HotelRepository $hotelRepository;

    public function __construct(HotelRepository $hotelRepository)
    {
        $this->hotelRepository = $hotelRepository;
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
}
