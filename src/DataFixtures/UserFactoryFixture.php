<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class UserFactoryFixture extends Fixture implements FixtureGroupInterface
{

    public static function getGroups(): array
    {
        return ['user_factory'];
    }

    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(10); // Create 10 users with fake data
    }
}
