<?php

namespace App\DataFixtures;

use App\Factory\BookingFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookingFactoryFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public static function getGroups(): array
    {
        return ['booking_factory'];
    }

    public function getDependencies(): array
    {
        return [
            UserFactoryFixture::class,
            RoomFactoryFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        BookingFactory::createMany(20);
    }
}
