<?php

namespace App\Controller\Page;

use App\Controller\BaseAbstractController;
use App\DTO\Project\ProjectPageDTO;
use App\Entity\Project;
use App\Form\ProjectType;
use App\Service\Project\ProjectPageBuilder;
use App\Service\Project\ProjectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectPageController extends BaseAbstractController
{
    #[Route('/project', name: 'app_project', methods: ['GET'])]
    public function index(
        Request $request,
        ProjectService $service,
        ProjectPageBuilder $projectPageBuilder,
    ): Response
    {
        $projects = $service->getUserProjects($this->getAuthorizedUser());
        $page = $projectPageBuilder->build($projects);

        return $this->renderProjectPage($request, $this->buildRenderContext($page));
    }

    #[Route('/project/{shortId<[A-Za-z0-9]{10}>}', name: 'app_project_show', methods: ['GET'])]
    public function show(
        Request $request,
        string $shortId,
        ProjectService $service,
        ProjectPageBuilder $projectPageBuilder,
    ): Response
    {
        $user = $this->getAuthorizedUser();
        $projects = $service->getUserProjects($user);
        $selectedProject = $service->findUserProjectByShortId($user, $shortId);

        if (null === $selectedProject) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $page = $projectPageBuilder->build($projects, $selectedProject);

        return $this->renderProjectPage($request, $this->buildRenderContext($page));
    }

    #[Route('/project/create', name: 'app_project_create', methods: ['GET'])]
    public function create(
        Request $request,
        ProjectService $service,
        ProjectPageBuilder $projectPageBuilder,
    ): Response {
        $user = $this->getAuthorizedUser();
        $projects = $service->getUserProjects($user);

        $project = $service->createProject($user);
        $form = $this->createForm(ProjectType::class, $project, [
            'attr' => [
                'class' => 'project-form',
            ],
        ]);

        $page = $projectPageBuilder->build($projects, null, 'create');

        $createContext = $this->buildRenderContext($page, [
            'projectForm' => $form->createView(),
            'projectFormAction' => $this->generateUrl('app_project_create'),
            'projectFormCancelUrl' => $this->generateUrl('app_project'),
            'projectFormSubmitLabel' => 'Сохранить',
            'projectApiUrl' => $this->generateUrl('api_project_create'),
            'projectApiMethod' => 'POST'
        ]);

        return $this->renderProjectPage($request, $createContext);
    }

    #[Route('/project/{shortId<[A-Za-z0-9]{10}>}/edit', name: 'app_project_edit', methods: ['GET'])]
    public function edit(
        Request $request,
        string $shortId,
        ProjectService $service,
        ProjectPageBuilder $projectPageBuilder,
    ): Response {
        $user = $this->getAuthorizedUser();
        $projects = $service->getUserProjects($user);
        $project = $service->findUserProjectByShortId($user, $shortId);

        if (null === $project) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $form = $this->createForm(ProjectType::class, $project, [
            'attr' => [
                'class' => 'project-form',
            ],
        ]);

        $page = $projectPageBuilder->build($projects, $project, 'edit');
        $editContext = $this->buildRenderContext($page, [
            'projectForm' => $form->createView(),
            'projectFormAction' => $this->generateUrl('app_project_edit', ['shortId' => $project->getShortId()]),
            'projectFormCancelUrl' => $this->generateUrl('app_project_show', ['shortId' => $project->getShortId()]),
            'projectFormSubmitLabel' => 'Сохранить изменения',
            'projectApiUrl' => $this->generateUrl('api_project_update', ['shortId' => $project->getShortId()]),
            'projectApiMethod' => 'PATCH'
        ]);

        return $this->renderProjectPage($request, $editContext);
    }

    private function renderProjectPage(Request $request, array $context): Response
    {
        return $this->renderFrameOrPage(
            $request,
            'project/index.html.twig',
            'project/blocks/_project_content_frame.html.twig',
            $context,
            'project_content',
        );
    }

    private function buildRenderContext(ProjectPageDTO $page, array $extra = []): array
    {
        return array_merge([
            'page' => $page,
            'projectView' => $page->projectView,
        ], $extra);
    }
}
