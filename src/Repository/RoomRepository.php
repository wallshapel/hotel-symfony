<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 *
 * @method Room|null find($id, $lockMode = null, $lockVersion = null)
 * @method Room|null findOneBy(array $criteria, array $orderBy = null)
 * @method Room[]    findAll()
 * @method Room[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function save(Room $room, bool $flush = true): void
    {
        $this->getEntityManager()->persist($room);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Room $room, bool $flush = true): void
    {
        $this->getEntityManager()->remove($room);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
