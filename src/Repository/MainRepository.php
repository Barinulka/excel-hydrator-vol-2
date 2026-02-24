<?php

namespace App\Repository;

use App\Entity\Main;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Main>
 */
class MainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Main::class);
    }

        public function findMainPageData(): ?Main
        {
            return $this->createQueryBuilder('m')
                ->orderBy('m.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        }
}
