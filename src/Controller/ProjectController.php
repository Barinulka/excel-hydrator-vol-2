<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectType;
use App\Service\ProjectService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectController extends BaseAbstractController
{
    #[Route('/project', name: 'app_project', methods: ['GET'])]
    public function index(Request $request, ProjectService $service): Response
    {
        $user = $this->getAuthorizedUser();

        return $this->renderProjectPage($request, $this->buildListContext($service, $user));
    }

    #[Route('/project/{shortId<[A-Za-z0-9]{10}>}', name: 'app_project_show', methods: ['GET'])]
    public function show(Request $request, string $shortId, ProjectService $service): Response
    {
        $user = $this->getAuthorizedUser();
        $selectedProject = $service->findUserProjectByShortId($user, $shortId);

        if (null === $selectedProject) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        return $this->renderProjectPage($request, $this->buildListContext($service, $user, $selectedProject));
    }

    #[Route('/project/create', name: 'app_project_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        ProjectService $service,
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
            $listContext = $this->buildListContext($service, $user, $project);

            if ($this->isProjectContentFrameRequest($request)) {
                return $this->render('project/blocks/_project_content_frame.html.twig', $listContext);
            }

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $project->getShortId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $createContext = $this->buildListContext($service, $user);
        $createContext['projectView'] = 'create';
        $createContext['projectForm'] = $form->createView();
        $createContext['projectFormAction'] = $this->generateUrl('app_project_create');
        $createContext['projectFormCancelUrl'] = $this->generateUrl('app_project');
        $createContext['projectFormSubmitLabel'] = 'Сохранить';

        return $this->renderProjectPage($request, $createContext);
    }

    #[Route('/project/{shortId<[A-Za-z0-9]{10}>}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        string $shortId,
        ProjectService $service,
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
            $listContext = $this->buildListContext($service, $user, $project);

            if ($this->isProjectContentFrameRequest($request)) {
                return $this->render('project/blocks/_project_content_frame.html.twig', $listContext);
            }

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $project->getShortId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $editContext = $this->buildListContext($service, $user, $project);
        $editContext['projectView'] = 'edit';
        $editContext['projectForm'] = $form->createView();
        $editContext['projectFormAction'] = $this->generateUrl('app_project_edit', ['shortId' => $project->getShortId()]);
        $editContext['projectFormCancelUrl'] = $this->generateUrl('app_project_show', ['shortId' => $project->getShortId()]);
        $editContext['projectFormSubmitLabel'] = 'Сохранить изменения';

        return $this->renderProjectPage($request, $editContext);
    }

    private function renderProjectPage(Request $request, array $context): Response
    {
        if ($this->isProjectContentFrameRequest($request)) {
            return $this->render('project/blocks/_project_content_frame.html.twig', $context);
        }

        return $this->render('project/index.html.twig', $context);
    }

    private function isProjectContentFrameRequest(Request $request): bool
    {
        return 'project_content' === $request->headers->get('Turbo-Frame');
    }

    private function buildListContext(ProjectService $service, User $user, ?Project $selectedProject = null): array
    {
        $projects = $service->getUserProjects($user);
        $selectedProjectInList = null;

        if ([] !== $projects) {
            if (null !== $selectedProject) {
                foreach ($projects as $project) {
                    if ($project->getId() === $selectedProject->getId()) {
                        $selectedProjectInList = $project;
                        break;
                    }
                }
            }

            $selectedProjectInList ??= $projects[0];
        }

        return [
            'projects' => $projects,
            'projectView' => 'list',
            'selectedProject' => $selectedProjectInList,
            'projectModelStubs' => $this->buildProjectModelStubs($selectedProjectInList),
        ];
    }

    private function buildProjectModelStubs(?Project $selectedProject): array
    {
        if (null === $selectedProject) {
            return [];
        }

        $projectTitle = $selectedProject->getTitle() ?? 'Проект';

        return [
            [
                'title' => sprintf('%s / Базовый сценарий', $projectTitle),
                'description' => 'Заглушка модели: здесь позже появятся реальные параметры и показатели.',
            ],
            [
                'title' => sprintf('%s / Оптимистичный сценарий', $projectTitle),
                'description' => 'Заглушка модели: карточка для демонстрации переключения по проектам.',
            ],
        ];
    }
}
