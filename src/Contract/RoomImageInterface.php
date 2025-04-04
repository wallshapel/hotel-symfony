<?php

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

interface RoomImageInterface
{
    public function uploadRoomImage(int $roomId, Request $request): array;
    public function updateRoomImage(int $imageId, Request $request): array;
    public function getRoomImages(int $roomId): array;
}
