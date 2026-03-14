<?php

namespace App\Controller;

use App\Exception\Excel\ExcelValidationException;
use App\Exception\Excel\GoExcelHydratorException;
use App\Service\ExcelModelGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ExcelController extends AbstractController
{
    #[Route('/excel', name: 'app_excel', methods: ['POST'])]
    public function generate(Request $request, ExcelModelGenerator $generator): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $result = $generator->generateFromRequest($payload);
        } catch (\JsonException) {
            return $this->json([
                'message' => 'Невалидный JSON.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (ExcelValidationException|GoExcelHydratorException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Model generated',
            ...$result,
        ]);
    }

    #[Route('/excel/output/{filename}', name: 'app_download_excel', methods: ['GET'])]
    public function download(string $filename, KernelInterface $kernel): BinaryFileResponse
    {
        if (1 !== preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            throw $this->createNotFoundException();
        }

        $path = $kernel->getProjectDir().'/excel/output/'.$filename;
        if (!file_exists($path)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }
}
