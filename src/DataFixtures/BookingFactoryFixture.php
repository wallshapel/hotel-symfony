<?php

namespace App\DataFixtures;

use App\Factory\BookingFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class BookingFactoryFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['booking_factory'];
    }

    public function load(ObjectManager $manager): void
    {
        BookingFactory::createMany(20);
    }
}
