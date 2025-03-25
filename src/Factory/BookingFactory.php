<?php

namespace App\Factory;

use App\Entity\Booking;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Proxy;

final class BookingFactory extends PersistentProxyObjectFactory
{
    private UserRepository $userRepository;
    private RoomRepository $roomRepository;

    public function __construct(UserRepository $userRepository, RoomRepository $roomRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->roomRepository = $roomRepository;
    }

    public static function class(): string
    {
        return Booking::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        $startDate = $faker->dateTimeBetween('now', '+30 days');
        $endDate = (clone $startDate)->modify('+' . random_int(1, 5) . ' days');

        $users = $this->userRepository->findUsersWithRole('ROLE_USER');
        $rooms = $this->roomRepository->findAll();

        return [
            'user' => $faker->randomElement($users),
            'room' => $faker->randomElement($rooms),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $faker->randomElement(['pending', 'reserved']),
            'createdAt' => new \DateTime(),
        ];
    }
}
