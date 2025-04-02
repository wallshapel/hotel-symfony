<?php

namespace App\Factory;

use App\Entity\Booking;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

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
        $user = $faker->randomElement($users);

        $availableRooms = $this->roomRepository->findBy(['status' => 'available']);
        if (empty($availableRooms)) {
            throw new \RuntimeException('No available rooms to create a reservation.');
        }

        $room = $faker->randomElement($availableRooms);

        $room->setStatus('unavailable');

        return [
            'user' => $user,
            'room' => $room,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'createdAt' => new \DateTime(),
        ];
    }
}
