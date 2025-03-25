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

        $pendingRooms = $this->roomRepository->findBy(['status' => 'pending']);

        $reservedRooms = $this->roomRepository->findBy(['status' => 'reserved']);

        $status = $faker->randomElement(['pending', 'reserved']);

        if ($status === 'reserved') {
            $room = $faker->randomElement($reservedRooms);
            $room->setStatus('reserved');
        } else {
            $room = $faker->randomElement($pendingRooms);
        }

        return [
            'user' => $user,
            'room' => $room,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'createdAt' => new \DateTime(),
        ];
    }
}
