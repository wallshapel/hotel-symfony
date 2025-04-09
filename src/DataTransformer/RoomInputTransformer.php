<?php

namespace App\DataTransformer;

use App\Entity\Hotel;
use App\Entity\Room;

class RoomInputTransformer
{
    public function fromArray(array $data, Hotel $hotel): Room
    {
        $room = new Room();
        $room->setNumber($data['number'] ?? '');
        $room->setType($data['type'] ?? '');
        $room->setCapacity((int) ($data['capacity'] ?? 0));
        $room->setPrice((float) ($data['price'] ?? 0));
        $room->setHotel($hotel);

        if (isset($data['status'])) {
            $room->setStatus($data['status']);
        }

        return $room;
    }

    public function updateFromArray(Room $room, array $data): Room
    {
        if (isset($data['number'])) {
            $room->setNumber($data['number']);
        }

        if (isset($data['type'])) {
            $room->setType($data['type']);
        }

        if (isset($data['capacity'])) {
            $room->setCapacity((int) $data['capacity']);
        }

        if (isset($data['price'])) {
            $room->setPrice((float) $data['price']);
        }

        if (isset($data['status'])) {
            $room->setStatus($data['status']);
        }

        return $room;
    }
}
