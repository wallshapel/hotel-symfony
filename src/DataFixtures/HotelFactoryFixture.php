<?php

namespace App\DataFixtures;

use App\Factory\HotelFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class HotelFactoryFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['hotel_factory'];
    }

    public function load(ObjectManager $manager): void
    {
        HotelFactory::createMany(10);
    }
}
