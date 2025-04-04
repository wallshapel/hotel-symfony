<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFactoryFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{

    public static function getGroups(): array
    {
        return ['user_factory'];
    }

    public function getDependencies(): array
    {
        return [UserAdmin::class];
    }

    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(10); // Create 10 users with fake data
    }
}
