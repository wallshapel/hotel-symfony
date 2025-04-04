<?php

namespace App\Contract;

interface UserRegistrationInterface
{
    public function registerUser(array $data): array;
}
