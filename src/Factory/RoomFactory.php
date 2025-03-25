<?php

namespace App\Factory;

use App\Entity\Room;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class RoomFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Room::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'number' => self::faker()->unique()->numerify('Room ###'),
            'type' => self::faker()->randomElement(['single', 'double', 'suite']),
            'capacity' => self::faker()->numberBetween(1, 6),
            'price' => self::faker()->randomFloat(2, 50, 500),
            'status' => self::faker()->randomElement(['pending', 'reserved', 'available']),
            'hotel' => HotelFactory::random(),
        ];
    }
}
