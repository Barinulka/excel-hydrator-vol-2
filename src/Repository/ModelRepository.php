<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Model>
 */
class ModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Model::class);
    }

    public function getNextVersionNumberForProject(Project $project): int
    {
        $qb = $this->createQueryBuilder('m');
        $maxVersion = $qb
            ->select('MAX(m.versionNumber)')
            ->where('m.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxVersion) + 1;
    }

    public function shortIdExists(string $shortId): bool
    {
        $qb = $this->createQueryBuilder('m');
        $count = $qb
            ->select('COUNT(m.id)')
            ->where('m.short_id = :shortId')
            ->setParameter('shortId', $shortId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function save(Model $model, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($model);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
