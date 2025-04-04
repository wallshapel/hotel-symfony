<?php

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

interface HotelImageInterface
{
    public function uploadHotelImage(int $id, Request $request): array;
    public function updateHotelImage(int $id, Request $request): array;
    public function getHotelImages(int $id): array;
}
