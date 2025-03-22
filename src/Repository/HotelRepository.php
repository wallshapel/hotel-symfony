<?php

namespace App\Repository;

use App\Entity\Hotel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hotel>
 *
 * @method Hotel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hotel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hotel[]    findAll()
 * @method Hotel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotel::class);
    }

    public function save(Hotel $hotel, bool $flush = true): void
    {
        $this->getEntityManager()->persist($hotel);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Hotel $hotel, bool $flush = true): void
    {
        $this->getEntityManager()->remove($hotel);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
