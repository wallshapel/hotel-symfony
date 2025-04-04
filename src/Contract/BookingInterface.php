<?php

namespace App\Contract;

interface BookingInterface
{
    public function getAllReservationsPaginated(array $filters): array;

    public function getPastBookingsPaginated(array $filters): array;

    public function getCurrentBookingsPaginated(array $filters): array;

    public function getFutureBookingsPaginated(array $filters): array;

    public function create(array $data): array;

    public function update(int $id, array $data): array;

    public function delete(int $id): array;
}
