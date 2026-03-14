<?php

namespace App\Service;

use App\DTO\ExcelInputData;
use App\DTO\GoHydrationDTO;
use App\Exception\Excel\ExcelValidationException;
use App\Service\Excel\ExcelHydratorInterface;
use App\Service\Validator\ExcelInputValidator;

final class ExcelModelGenerator
{
    public function __construct(
        private ExcelHydratorInterface $excelHydrator,
        private ExcelInputValidator $validator,
        private string $defaultTemplate = 'model.xlsx',
        private string $downloadBaseUrl = '/excel/output/',
    ) {
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array{filename: string, download_url: string}
     *
     * @throws ExcelValidationException
     */
    public function generateFromRequest(array $request): array
    {
        /** @var array<string, array<string, mixed>> $sheetData */
        $sheetData = $request['sheetData'] ?? $request['data'] ?? [];
        $input = new ExcelInputData($sheetData);
        $this->validator->validate($input);

        $template = $request['template'] ?? $this->resolveTemplate($request['modelType'] ?? null);
        $hydrationDTO = new GoHydrationDTO(
            template: $template,
            data: $input->sheetData,
        );

        return $this->generate($hydrationDTO);
    }

    private function resolveTemplate(mixed $modelType): string
    {
        // TODO: Позже здесь будет маппинг modelType -> template.
        return $this->defaultTemplate;
    }

    /**
     * @return array{filename: string, download_url: string}
     */
    private function generate(GoHydrationDTO $hydrationDTO): array
    {
        $filename = $this->excelHydrator->hydrate($hydrationDTO->template, $hydrationDTO->data);

        return [
            'filename' => $filename,
            'download_url' => rtrim($this->downloadBaseUrl, '/').'/'.$filename,
        ];
    }
}
