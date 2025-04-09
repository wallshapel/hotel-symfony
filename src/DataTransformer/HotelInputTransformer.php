<?php

namespace App\DataTransformer;

use App\Entity\Hotel;

class HotelInputTransformer
{
    public function fromArray(array $data): Hotel
    {
        $hotel = new Hotel();
        $hotel->setName($data['name'] ?? '');
        $hotel->setAddress($data['address'] ?? '');
        $hotel->setCity($data['city'] ?? '');
        $hotel->setCountry($data['country'] ?? '');
        $hotel->setDescription($data['description'] ?? '');
        $hotel->setCreatedAt(new \DateTimeImmutable());

        return $hotel;
    }

    public function updateFromArray(Hotel $hotel, array $data): Hotel
    {
        if (isset($data['name'])) $hotel->setName($data['name']);
        if (isset($data['address'])) $hotel->setAddress($data['address']);
        if (isset($data['city'])) $hotel->setCity($data['city']);
        if (isset($data['country'])) $hotel->setCountry($data['country']);
        if (isset($data['description'])) $hotel->setDescription($data['description']);

        return $hotel;
    }
}
