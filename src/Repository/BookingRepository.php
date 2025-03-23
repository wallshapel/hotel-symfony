<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $booking, bool $flush = true): void
    {
        $this->getEntityManager()->persist($booking);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $booking, bool $flush = true): void
    {
        $this->getEntityManager()->remove($booking);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find bookings for a specific user
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('b.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
