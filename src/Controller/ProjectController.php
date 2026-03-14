<?php

namespace App\Controller;

use App\DTO\Projects\ProjectPageDTO;
use App\Form\ProjectType;
use App\Service\ProjectPageBuilder;
use App\Service\ProjectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectController extends BaseAbstractController
{
    #[Route('/project', name: 'app_project', methods: ['GET'])]
    public function index(Request $request, ProjectPageBuilder $projectPageBuilder): Response
    {
        $page = $projectPageBuilder->build($this->getAuthorizedUser());

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
        $selectedProject = $service->findUserProjectByShortId($user, $shortId);

        if (null === $selectedProject) {
            throw $this->createNotFoundException('Проект не найден.');
        }
        $page = $projectPageBuilder->build($user, $selectedProject);

        return $this->renderProjectPage($request, $this->buildRenderContext($page));
    }

    #[Route('/project/create', name: 'app_project_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        ProjectService $service,
        ProjectPageBuilder $projectPageBuilder,
    ): Response {
        $user = $this->getAuthorizedUser();

        $project = $service->createProject($user);
        $form = $this->createForm(ProjectType::class, $project, [
            'attr' => [
                'class' => 'project-form',
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->saveProject($project);
            $this->addFlash('success', 'Проект успешно создан.');
            $page = $projectPageBuilder->build($user, $project);
            $listContext = $this->buildRenderContext($page);

            if ($this->isTurboFrameRequest($request, 'project_content')) {
                return $this->render('project/blocks/_project_content_frame.html.twig', $listContext);
            }

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $project->getShortId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $page = $projectPageBuilder->build($user, null, 'create');
        $createContext = $this->buildRenderContext($page, [
            'projectForm' => $form->createView(),
            'projectFormAction' => $this->generateUrl('app_project_create'),
            'projectFormCancelUrl' => $this->generateUrl('app_project'),
            'projectFormSubmitLabel' => 'Сохранить',
        ]);

        return $this->renderProjectPage($request, $createContext);
    }

    #[Route('/project/{shortId<[A-Za-z0-9]{10}>}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        string $shortId,
        ProjectService $service,
        ProjectPageBuilder $projectPageBuilder,
    ): Response {
        $user = $this->getAuthorizedUser();
        $project = $service->findUserProjectByShortId($user, $shortId);

        if (null === $project) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $form = $this->createForm(ProjectType::class, $project, [
            'attr' => [
                'class' => 'project-form',
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->saveProject($project);
            $this->addFlash('success', 'Проект успешно обновлен.');
            $page = $projectPageBuilder->build($user, $project);
            $listContext = $this->buildRenderContext($page);

            if ($this->isTurboFrameRequest($request, 'project_content')) {
                return $this->render('project/blocks/_project_content_frame.html.twig', $listContext);
            }

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $project->getShortId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $page = $projectPageBuilder->build($user, $project, 'edit');
        $editContext = $this->buildRenderContext($page, [
            'projectForm' => $form->createView(),
            'projectFormAction' => $this->generateUrl('app_project_edit', ['shortId' => $project->getShortId()]),
            'projectFormCancelUrl' => $this->generateUrl('app_project_show', ['shortId' => $project->getShortId()]),
            'projectFormSubmitLabel' => 'Сохранить изменения',
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
