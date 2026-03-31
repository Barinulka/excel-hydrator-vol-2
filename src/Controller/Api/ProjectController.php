<?php

namespace App\Controller\Api;

use App\Controller\BaseApiAbstractController;
use App\Mapper\Project\ProjectRequestMapper;
use App\Mapper\Project\ProjectResponseMapper;
use App\Service\Project\ProjectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProjectController extends BaseApiAbstractController
{
    #[Route('/api/projects', name: 'api_project_create', methods: ['POST'])]
    public function create(
        Request $request,
        ProjectService $projectService,
        ProjectResponseMapper $responseMapper,
        ProjectRequestMapper $requestMapper,
        ValidatorInterface $validator
    ): Response
    {
        $data = $this->getJsonRequestData($request);

        $dto = $requestMapper->mapCreateDto($data);

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $project = $projectService->createProject($this->getAuthorizedUser());

        $projectService->applyCreateRequest($project, $dto);

        $projectService->saveProject($project);

        return $this->json($responseMapper->map($project), Response::HTTP_CREATED);
    }

    #[Route('/api/projects/{shortId<[A-Za-z0-9]{10}>}', name: 'api_project_update', methods: ['PATCH'])]
    public function update(
        Request $request,
        string $shortId,
        ProjectService $projectService,
        ProjectResponseMapper $responseMapper,
        ProjectRequestMapper $requestMapper,
        ValidatorInterface $validator
    ): Response
    {
        $project = $projectService->findUserProjectByShortId($this->getAuthorizedUser(), $shortId);

        if (null === $project) {
            return $this->jsonNotFoundResponse('Проект не найден');
        }

        $data = $this->getJsonRequestData($request);

        $dto = $requestMapper->mapUpdateDto($data);

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $projectService->applyUpdateRequest($project, $dto);

        $projectService->saveProject($project);

        return $this->json($responseMapper->map($project), Response::HTTP_OK);
    }
}
