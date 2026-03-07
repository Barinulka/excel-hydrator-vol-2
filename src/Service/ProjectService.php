<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;

class ProjectService
{
    private const SHORT_ID_LENGTH = 10;
    private const SHORT_ID_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function __construct(
        private ProjectRepository $projectRepository,
    ) {
    }

    public function getUserProjects(User $user): array
    {
        $projects = $this->projectRepository->getProjectByUser($user);
        $needFlush = false;
        $reservedShortIds = [];

        foreach ($projects as $project) {
            if (null !== $project->getShortId()) {
                $reservedShortIds[$project->getShortId()] = true;
            }
        }

        foreach ($projects as $project) {
            if (null === $project->getPublicId()) {
                $project->ensurePublicId();
                $needFlush = true;
            }

            if (null === $project->getShortId()) {
                $project->setShortId($this->generateUniqueShortId($reservedShortIds));
                $needFlush = true;
            }
        }

        if ($needFlush) {
            $this->projectRepository->flush();
        }

        return $projects;
    }

    public function createProject(User $user): Project
    {
        $project = new Project();
        $project->setAuthor($user);

        return $project;
    }

    public function saveProject(Project $project): void
    {
        $project->ensurePublicId();
        if (null === $project->getShortId()) {
            $reservedShortIds = [];
            $project->setShortId($this->generateUniqueShortId($reservedShortIds));
        }

        $this->projectRepository->save($project);
    }

    public function findUserProjectByShortId(User $user, string $shortId): ?Project
    {
        if (!preg_match('/^[A-Za-z0-9]{10}$/', $shortId)) {
            return null;
        }

        foreach ($this->getUserProjects($user) as $project) {
            if ($project->getShortId() === $shortId) {
                return $project;
            }
        }

        return null;
    }

    private function generateUniqueShortId(array &$reservedShortIds): string
    {
        do {
            $shortId = $this->generateShortId();
        } while (isset($reservedShortIds[$shortId]) || $this->projectRepository->shortIdExists($shortId));

        $reservedShortIds[$shortId] = true;

        return $shortId;
    }

    private function generateShortId(): string
    {
        $alphabet = self::SHORT_ID_ALPHABET;
        $maxIndex = strlen($alphabet) - 1;
        $shortId = '';

        for ($index = 0; $index < self::SHORT_ID_LENGTH; ++$index) {
            $shortId .= $alphabet[random_int(0, $maxIndex)];
        }

        return $shortId;
    }
}
