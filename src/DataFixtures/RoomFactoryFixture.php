<?php

namespace App\DataFixtures;

use App\Factory\HotelFactory;
use App\Factory\RoomFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoomFactoryFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{

    public static function getGroups(): array
    {
        return ['room_factory'];
    }

    public function getDependencies(): array
    {
        return [HotelFactoryFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $hotels = HotelFactory::all();

        // For each existing hotel, create 30 rooms
        foreach ($hotels as $hotel) {
            RoomFactory::createMany(30, [
                'hotel' => $hotel,
            ]);
        }
    }
}
