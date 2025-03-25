<?php

namespace App\Factory;

use App\Entity\Hotel;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class HotelFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Hotel::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company . ' Hotel',
            'address' => self::faker()->address,
            'city' => self::faker()->city,
            'country' => self::faker()->country,
            'description' => self::faker()->paragraph,
            'createdAt' => self::faker()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
