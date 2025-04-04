<?php

namespace App\Contract;

interface RoomInterface
{
    public function create(array $data): array;
    public function getById(int $id): array;
    public function update(int $id, array $data): array;
    public function delete(int $id): array;
    public function getAvailableRoomsPaginated(array $filters): array;
    public function getRoomImages(int $id): array;
}
