<?php

namespace App\Service;

use App\Entity\Model;
use App\Entity\ModelTabData;
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

    public function findProjectModelByShortId(Project $project, string $shortId): ?Model
    {
        if (!preg_match('/^[A-Za-z0-9]{10}$/', $shortId)) {
            return null;
        }

        return $this->modelRepository->findOneByProjectAndShortId($project, $shortId);
    }

    public function getDefaultTimeParamsFormData(): array
    {
        return [
            'investmentStartMonth' => (new \DateTimeImmutable())->format('Y-m'),
            'investmentDurationMonths' => 24,
            'commercialOperationDurationMonths' => 24,
            'forecastStep' => 'month',
        ];
    }

    public function getTimeParamsFormData(Model $model): array
    {
        $defaults = $this->getDefaultTimeParamsFormData();
        $tabData = $this->findModelTabDataByKey($model, 'time_params');

        if (null === $tabData) {
            return $defaults;
        }

        $payload = $tabData->getPayload();
        $investmentStartMonth = $this->normalizeStoredMonthToYm((string) ($payload['investment_start_date'] ?? ''));

        $forecastStep = (string) ($payload['forecast_step'] ?? $defaults['forecastStep']);
        if (!in_array($forecastStep, ['month', 'quarter', 'year'], true)) {
            $forecastStep = $defaults['forecastStep'];
        }

        return [
            'investmentStartMonth' => '' !== $investmentStartMonth ? $investmentStartMonth : $defaults['investmentStartMonth'],
            'investmentDurationMonths' => max(1, (int) ($payload['investment_duration_months'] ?? $defaults['investmentDurationMonths'])),
            'commercialOperationDurationMonths' => max(1, (int) ($payload['commercial_operation_duration_months'] ?? $defaults['commercialOperationDurationMonths'])),
            'forecastStep' => $forecastStep,
        ];
    }

    public function upsertTimeParamsTabData(Model $model, array $data): void
    {
        $tabData = $this->findModelTabDataByKey($model, 'time_params');

        if (null === $tabData) {
            $tabData = new ModelTabData();
            $tabData->setTabKey('time_params');
            $model->addModelTabData($tabData);
        }

        $tabData->setPayload([
            'investment_start_date' => $this->normalizeMonthStartToFirstDay((string) ($data['investmentStartMonth'] ?? '')),
            'investment_duration_months' => (int) ($data['investmentDurationMonths'] ?? 0),
            'commercial_operation_duration_months' => (int) ($data['commercialOperationDurationMonths'] ?? 0),
            'forecast_step' => (string) ($data['forecastStep'] ?? ''),
        ]);
    }

    private function buildDefaultTitle(Project $project, ?int $versionNumber): string
    {
        $safeVersion = $versionNumber ?? 1;

        return sprintf('%s v%d', $project->getTitle() ?? 'Проект', $safeVersion);
    }

    private function normalizeMonthStartToFirstDay(string $value): string
    {
        $monthDate = \DateTimeImmutable::createFromFormat('!Y-m', $value);

        return $monthDate ? $monthDate->format('Y-m-01') : '';
    }

    private function normalizeStoredMonthToYm(string $value): string
    {
        if ('' === $value) {
            return '';
        }

        $fullDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($fullDate instanceof \DateTimeImmutable) {
            return $fullDate->format('Y-m');
        }

        $monthDate = \DateTimeImmutable::createFromFormat('!Y-m', $value);

        return $monthDate ? $monthDate->format('Y-m') : '';
    }

    private function findModelTabDataByKey(Model $model, string $key): ?ModelTabData
    {
        foreach ($model->getModelTabData() as $modelTabData) {
            if ($modelTabData->getTabKey() === $key) {
                return $modelTabData;
            }
        }

        return null;
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
