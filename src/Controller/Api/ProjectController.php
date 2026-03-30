<?php

namespace App\Controller\Api;

use App\Controller\BaseApiAbstractController;
use App\DTO\Project\Request\CreateProjectRequestDTO;
use App\DTO\Project\Request\UpdateProjectRequestDTO;
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
        ValidatorInterface $validator
    ): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new CreateProjectRequestDTO();
        $dto->title = $data['title'] ?? null;
        $dto->code = $data['code'] ?? null;
        $dto->description = $data['description'] ?? null;

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $project = $projectService->createProject($this->getAuthorizedUser());
        $project->setTitle($dto->title);
        $project->setCode($dto->code);
        $project->setDescription($dto->description);

        $projectService->saveProject($project);

        return $this->json($responseMapper->map($project), Response::HTTP_CREATED);
    }

    #[Route('/api/projects/{shortId<[A-Za-z0-9]{10}>}', name: 'api_project_update', methods: ['PATCH'])]
    public function update(
        Request $request,
        string $shortId,
        ProjectService $projectService,
        ProjectResponseMapper $responseMapper,
        ValidatorInterface $validator
    ): Response
    {
        $project = $projectService->findUserProjectByShortId($this->getAuthorizedUser(), $shortId);

        if (null === $project) {
            return $this->jsonNotFoundResponse('Проект не найден');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new UpdateProjectRequestDTO();
        $dto->title = $data['title'] ?? null;
        $dto->code = $data['code'] ?? null;
        $dto->description = $data['description'] ?? null;

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->jsonValidationErrorResponse($errors);
        }

        $project->setTitle($dto->title);
        $project->setCode($dto->code);
        $project->setDescription($dto->description);

        $projectService->saveProject($project);

        return $this->json($responseMapper->map($project), Response::HTTP_OK);
    }
}
