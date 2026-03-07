<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function getProjectByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->where('p.author = :author')
            ->setParameter('author', $user)
            ->orderBy('p.createdAt', 'DESC');

        return $qb->getQuery()->getResult() ?? [];
    }

    public function save(Project $project, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($project);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function shortIdExists(string $shortId): bool
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('COUNT(p.id)')
            ->where('p.shortId = :shortId')
            ->setParameter('shortId', $shortId);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
