<?php

namespace App\Repository;

use App\Entity\ModelTimeParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModelTimeParams>
 */
class ModelTimeParamsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModelTimeParams::class);
    }

    public function save(ModelTimeParams $modelTimeParams, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($modelTimeParams);

        if ($flush) {
            $entityManager->flush();
        }
    }
}
