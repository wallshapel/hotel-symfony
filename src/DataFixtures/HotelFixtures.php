<?php

namespace App\DataFixtures;

use App\Entity\Hotel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class HotelFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $hotels = [
            [
                'name' => 'Hotel Paradise',
                'address' => '123 Ocean Drive',
                'city' => 'Miami',
                'country' => 'USA',
                'description' => 'A luxurious hotel by the beach with stunning ocean views.',
            ],
            [
                'name' => 'Mountain Retreat',
                'address' => '456 Alpine Road',
                'city' => 'Denver',
                'country' => 'USA',
                'description' => 'Cozy lodge located in the heart of the Rocky Mountains.',
            ],
            [
                'name' => 'Urban Stay',
                'address' => '789 Downtown Ave',
                'city' => 'New York',
                'country' => 'USA',
                'description' => 'Modern hotel with easy access to city attractions and nightlife.',
            ],
        ];

        foreach ($hotels as $data) {
            $hotel = new Hotel();
            $hotel->setName($data['name']);
            $hotel->setAddress($data['address']);
            $hotel->setCity($data['city']);
            $hotel->setCountry($data['country']);
            $hotel->setDescription($data['description']);

            $manager->persist($hotel);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['hotels'];
    }
}
