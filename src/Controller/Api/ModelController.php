<?php

namespace App\Controller\Api;

use App\Controller\BaseApiAbstractController;
use App\Mapper\Model\RenameModelRequestMapper;
use App\Mapper\Model\UpdateTimeParamsRequestMapper;
use App\Service\ModelService;
use App\Service\Project\ProjectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ModelController extends BaseApiAbstractController
{
    #[Route('/api/projects/{projectShortId<[A-Za-z0-9]{10}>}/models', name: 'api_model_create', methods: ['POST'])]
    public function create(
        string $projectShortId,
        Request $request,
        ProjectService $projectService,
        ModelService $modelService,
        ValidatorInterface $validator,
        UpdateTimeParamsRequestMapper $updateTimeParamsRequestMapper,
    ): Response {
        $project = $projectService->findUserProjectByShortId($this->getAuthorizedUser(), $projectShortId);

        if (null === $project) {
            return $this->jsonNotFoundResponse('Проект не найден');
        }

        $data = $this->getJsonRequestData($request);

        $dto = $updateTimeParamsRequestMapper->mapCreateDto($data);

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $model = $modelService->createModel($project, $this->getAuthorizedUser());

        $modelService->upsertTimeParamsTabData($model, $dto);

        $modelService->saveModel($model);

        return $this->json([
            'message' => 'Модель создана.',
            'projectShortId' => $projectShortId,
            'modelShortId' => $model->getShortId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/projects/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/time-params', name: 'api_model_time_params_update', methods: ['PATCH'])]
    public function updateTimeParams(
        Request $request,
        string $projectShortId,
        string $modelShortId,
        ProjectService $projectService,
        ModelService $modelService,
        ValidatorInterface $validator,
        UpdateTimeParamsRequestMapper $updateTimeParamsRequestMapper,
    ): Response
    {
        $project = $projectService->findUserProjectByShortId($this->getAuthorizedUser(), $projectShortId);

        if (null === $project) {
            return $this->jsonNotFoundResponse('Проект не найден');
        }

        $model = $modelService->findProjectModelByShortId($project, $modelShortId);

        if (null === $model) {
            return $this->jsonNotFoundResponse('Модель не найдена');
        }

        $data = $this->getJsonRequestData($request);

        $dto = $updateTimeParamsRequestMapper->mapUpdateDto($data);

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $modelService->upsertTimeParamsTabData($model, $dto);

        $modelService->saveModel($model);

        return $this->json([
            'message' => 'Временные параметры обновлены',
            'modelShortId' => $modelShortId,
        ]);
    }

    #[Route(
        '/api/projects/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/title',
        name: 'api_model_title_update',
        methods: ['PATCH']
    )]
    public function updateTitle(
        string $projectShortId,
        string $modelShortId,
        Request $request,
        ProjectService $projectService,
        ModelService $modelService,
        ValidatorInterface $validator,
        RenameModelRequestMapper $renameMapper,
    ): Response {
        $project = $projectService->findUserProjectByShortId($this->getAuthorizedUser(), $projectShortId);

        if (null === $project) {
            return $this->jsonNotFoundResponse('Проект не найден');
        }

        $model = $modelService->findProjectModelByShortId($project, $modelShortId);

        if (null === $model) {
            return $this->jsonNotFoundResponse('Модель не найдена');
        }

        $data = $this->getJsonRequestData($request);

        $dto = $renameMapper->mapRenameDto($data);

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $modelService->applyRenameTitle($model, $dto);
        $modelService->saveModel($model);

        return $this->json([
            'message' => 'Название обновлено.',
            'title' => $model->getTitle(),
            'projectShortId' => $projectShortId,
            'modelShortId' => $modelShortId,
        ]);
    }
}
