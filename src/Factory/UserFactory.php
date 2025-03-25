<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class UserFactory extends PersistentProxyObjectFactory
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'username' => self::faker()->unique()->userName(),
            'roles' => ['ROLE_USER'],
            'password' => 'password123',
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            $hashed = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashed);
        });
    }
}
