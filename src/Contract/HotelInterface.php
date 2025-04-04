<?php

namespace App\Contract;

interface HotelInterface
{
    public function create(array $data): array;
    public function update(int $id, array $data): array;
    public function delete(int $id): array;
}
