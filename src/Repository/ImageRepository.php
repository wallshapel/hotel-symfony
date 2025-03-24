<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 *
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function save(Image $image, bool $flush = true): void
    {
        $this->getEntityManager()->persist($image);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Image $image, bool $flush = true): void
    {
        $this->getEntityManager()->remove($image);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Image[]
     */
    public function findByHotelId(int $hotelId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.hotel = :hotelId')
            ->setParameter('hotelId', $hotelId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Image[]
     */
    public function findByRoomId(int $roomId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.room = :roomId')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }
}
