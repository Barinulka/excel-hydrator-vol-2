<?php

namespace App\Controller;

use App\Exception\Excel\ExcelValidationException;
use App\Exception\Excel\GoExcelHydratorException;
use App\Form\ModelCreateType;
use App\Service\Excel\ModelExcelPayloadBuilder;
use App\Service\ExcelModelGenerator;
use App\Service\ModelService;
use App\Service\ProjectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/project', name: 'app_model_')]
final class ModelController extends BaseAbstractController
{
    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        string $projectShortId,
        ProjectService $projectService,
        ModelService $modelService,
    ): Response
    {
        $user = $this->getAuthorizedUser();
        $project = $projectService->findUserProjectByShortId($user, $projectShortId);

        if (null === $project) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $model = $modelService->createModel($project, $user);
        $form = $this->createForm(ModelCreateType::class, $modelService->getDefaultTimeParamsFormData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $modelService->upsertTimeParamsTabData($model, $form->getData());
            $modelService->saveModel($model);
            $this->addFlash('success', sprintf('Модель %s успешно создана.', $model->getVersionLabel()));

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $projectShortId,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderModelPage($request, [
            'project' => $project,
            'model' => $model,
            'modelTab' => 'time_params',
            'modelPageTitle' => 'Создание модели',
            'modelSubmitLabel' => 'Создать модель',
            'modelCreateForm' => $form->createView(),
            'modelFormAction' => $this->generateUrl('app_model_create', [
                'projectShortId' => $projectShortId,
            ]),
            'modelTabTimeParamsUrl' => $this->generateUrl('app_model_create', [
                'projectShortId' => $projectShortId,
            ]),
        ]);
    }

    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        string $projectShortId,
        string $modelShortId,
        ProjectService $projectService,
        ModelService $modelService,
    ): Response
    {
        $user = $this->getAuthorizedUser();
        $project = $projectService->findUserProjectByShortId($user, $projectShortId);

        if (null === $project) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $model = $modelService->findProjectModelByShortId($project, $modelShortId);

        if (null === $model) {
            throw $this->createNotFoundException('Модель не найдена.');
        }

        $form = $this->createForm(ModelCreateType::class, $modelService->getTimeParamsFormData($model));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $modelService->upsertTimeParamsTabData($model, $form->getData());
            $modelService->saveModel($model);
            $this->addFlash('success', sprintf('Модель %s успешно обновлена.', $model->getVersionLabel()));

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $projectShortId,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderModelPage($request, [
            'project' => $project,
            'model' => $model,
            'modelTab' => 'time_params',
            'modelPageTitle' => 'Редактирование модели',
            'modelSubmitLabel' => 'Сохранить',
            'modelCreateForm' => $form->createView(),
            'modelFormAction' => $this->generateUrl('app_model_edit', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'modelTabTimeParamsUrl' => $this->generateUrl('app_model_edit', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'modelExportUrl' => $this->generateUrl('app_model_export_excel', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
        ]);
    }

    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/export-excel', name: 'export_excel', methods: ['POST'])]
    public function exportExcel(
        Request $request,
        string $projectShortId,
        string $modelShortId,
        ProjectService $projectService,
        ModelService $modelService,
        ModelExcelPayloadBuilder $modelExcelPayloadBuilder,
        ExcelModelGenerator $excelModelGenerator,
    ): Response
    {
        $user = $this->getAuthorizedUser();
        $project = $projectService->findUserProjectByShortId($user, $projectShortId);

        if (null === $project) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $model = $modelService->findProjectModelByShortId($project, $modelShortId);

        if (null === $model) {
            throw $this->createNotFoundException('Модель не найдена.');
        }

        $submittedToken = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid(sprintf('model_export_%s', $modelShortId), $submittedToken)) {
            throw $this->createAccessDeniedException('Недействительный CSRF токен.');
        }

        try {
            $requestPayload = $modelExcelPayloadBuilder->buildRequest($model);
            $result = $excelModelGenerator->generateFromRequest($requestPayload);
        } catch (ExcelValidationException|GoExcelHydratorException $exception) {
            $this->addFlash('error', sprintf('Не удалось выгрузить Excel: %s', $exception->getMessage()));

            return $this->redirectToRoute('app_model_edit', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_download_excel', [
            'filename' => $result['filename'],
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/rename', name: 'rename', methods: ['POST'])]
    public function rename(
        Request $request,
        string $projectShortId,
        string $modelShortId,
        ProjectService $projectService,
        ModelService $modelService,
    ): Response
    {
        $user = $this->getAuthorizedUser();
        $project = $projectService->findUserProjectByShortId($user, $projectShortId);

        if (null === $project) {
            throw $this->createNotFoundException('Проект не найден.');
        }

        $model = $modelService->findProjectModelByShortId($project, $modelShortId);

        if (null === $model) {
            throw $this->createNotFoundException('Модель не найдена.');
        }

        $submittedToken = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid(sprintf('model_rename_%s', $modelShortId), $submittedToken)) {
            if ($this->expectsJson($request)) {
                return $this->json([
                    'message' => 'Недействительный CSRF токен.',
                ], Response::HTTP_FORBIDDEN);
            }

            throw $this->createAccessDeniedException('Недействительный CSRF токен.');
        }

        $title = trim((string) $request->request->get('title', ''));
        if ('' === $title) {
            if ($this->expectsJson($request)) {
                return $this->json([
                    'message' => 'Ошибка валидации.',
                    'errors' => [
                        'title' => 'Название модели не может быть пустым.',
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->addFlash('error', 'Название модели не может быть пустым.');

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $projectShortId,
            ], Response::HTTP_SEE_OTHER);
        }

        if (mb_strlen($title) > 255) {
            if ($this->expectsJson($request)) {
                return $this->json([
                    'message' => 'Ошибка валидации.',
                    'errors' => [
                        'title' => 'Название модели не должно превышать 255 символов.',
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->addFlash('error', 'Название модели не должно превышать 255 символов.');

            return $this->redirectToRoute('app_project_show', [
                'shortId' => $projectShortId,
            ], Response::HTTP_SEE_OTHER);
        }

        $model->setTitle($title);
        $modelService->saveModel($model);

        if ($this->expectsJson($request)) {
            return $this->json([
                'message' => 'Название модели обновлено.',
                'title' => $model->getTitle(),
                'modelShortId' => $modelShortId,
            ]);
        }

        $this->addFlash('success', 'Название модели обновлено.');

        return $this->redirectToRoute('app_project_show', [
            'shortId' => $projectShortId,
        ], Response::HTTP_SEE_OTHER);
    }

    private function expectsJson(Request $request): bool
    {
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        return str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    private function renderModelPage(Request $request, array $context): Response
    {
        if ($this->isModelContentFrameRequest($request)) {
            return $this->render('model/blocks/_model_content_frame.html.twig', $context);
        }

        return $this->render('model/create.html.twig', $context);
    }

    private function isModelContentFrameRequest(Request $request): bool
    {
        return 'model_content' === $request->headers->get('Turbo-Frame');
    }
}
