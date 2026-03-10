<?php

namespace App\Service;

use App\Entity\Model;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ModelRepository;

class ModelService
{
    private const SHORT_ID_LENGTH = 10;
    private const SHORT_ID_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function __construct(
        private ModelRepository $modelRepository,
    ) {
    }

    public function createModel(Project $project, User $author): Model
    {
        $nextVersionNumber = $this->modelRepository->getNextVersionNumberForProject($project);
        $reservedShortIds = [];

        $model = new Model();
        $model->setProject($project);
        $model->setAuthor($author);
        $model->setVersionNumber($nextVersionNumber);
        $model->setShortId($this->generateUniqueShortId($reservedShortIds));
        $model->setStatus('draft');
        $model->setTitle($this->buildDefaultTitle($project, $nextVersionNumber));

        return $model;
    }

    public function saveModel(Model $model): void
    {
        $project = $model->getProject();
        if (null === $project) {
            throw new \LogicException('Нельзя сохранить модель без проекта.');
        }

        if (null === $model->getVersionNumber()) {
            $model->setVersionNumber($this->modelRepository->getNextVersionNumberForProject($project));
        }

        if (null === $model->getShortId()) {
            $reservedShortIds = [];
            $model->setShortId($this->generateUniqueShortId($reservedShortIds));
        }

        if (null === $model->getTitle() || '' === trim($model->getTitle())) {
            $model->setTitle($this->buildDefaultTitle($project, $model->getVersionNumber()));
        }

        $this->modelRepository->save($model);
    }

    private function buildDefaultTitle(Project $project, ?int $versionNumber): string
    {
        $safeVersion = $versionNumber ?? 1;

        return sprintf('%s v%d', $project->getTitle() ?? 'Проект', $safeVersion);
    }

    private function generateUniqueShortId(array &$reservedShortIds): string
    {
        do {
            $shortId = $this->generateShortId();
        } while (isset($reservedShortIds[$shortId]) || $this->modelRepository->shortIdExists($shortId));

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
