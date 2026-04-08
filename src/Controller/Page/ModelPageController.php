<?php

namespace App\Controller\Page;

use App\Controller\BaseAbstractController;
use App\Exception\Excel\ExcelValidationException;
use App\Exception\Excel\GoExcelHydratorException;
use App\Service\Excel\ModelExcelPayloadBuilder;
use App\Service\ExcelModelGenerator;
use App\Service\ModelService;
use App\Service\Project\ProjectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/project', name: 'app_model_')]
final class ModelPageController extends BaseAbstractController
{
    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/time-params', name: 'time_params', methods: ['GET'])]
    public function timeParams(
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

        $timeParams = $modelService->getTimeParamsFormData($model);

        return $this->renderModelPage($request, [
            'project' => $project,
            'model' => $model,
            'modelTab' => 'time_params',
            'timeParamsPageTitle' => 'Редактирование модели',
            'timeParamsSubmitLabel' => 'Сохранить',
            'timeParamsMode' => 'edit',
            'timeParams' => $timeParams,
            'modelTabTimeParamsUrl' => $this->generateUrl('app_model_time_params', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'modelTabCapexUrl' => $this->generateUrl('app_model_capex', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'modelExportUrl' => $this->generateUrl('app_model_export_excel', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'timeParamsApiUrl' => $this->generateUrl('api_model_time_params_update', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'timeParamsApiMethod' => 'PATCH',
        ]);
    }

    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/{modelShortId<[A-Za-z0-9]{10}>}/capex', name: 'capex', methods: ['GET'])]
    public function capex(
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

//        $capex = $modelService->getCapexFormData($model);
        $capex = [];

        return $this->renderModelPage($request, [
            'project' => $project,
            'model' => $model,
            'modelTab' => 'capex',
            'capexPageTitle' => 'Редактирование модели',
            'capexSubmitLabel' => 'Сохранить',
            'capexMode' => 'edit',
            'capex' => $capex,
            'modelTabTimeParamsUrl' => $this->generateUrl('app_model_time_params', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'modelTabCapexUrl' => $this->generateUrl('app_model_capex', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
            'modelExportUrl' => $this->generateUrl('app_model_export_excel', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ]),
//            'capexApiUrl' => $this->generateUrl('api_model_time_params_update', [
//                'projectShortId' => $projectShortId,
//                'modelShortId' => $modelShortId,
//            ]),
            'capexApiUrl' => '',
            'capexApiMethod' => 'PATCH',
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

            return $this->redirectToRoute('app_model_time_params', [
                'projectShortId' => $projectShortId,
                'modelShortId' => $modelShortId,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_download_excel', [
            'filename' => $result['filename'],
        ], Response::HTTP_SEE_OTHER);
    }

    private function renderModelPage(Request $request, array $context): Response
    {
        return $this->renderFrameOrPage(
            $request,
            'model/show.html.twig',
            'model/blocks/_model_content_frame.html.twig',
            $context,
            'model_content',
        );
    }
}
