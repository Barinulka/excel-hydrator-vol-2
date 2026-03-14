<?php

namespace App\Service\Excel;

use App\Exception\Excel\GoExcelHydratorException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoExcelHydrator implements ExcelHydratorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $goServiceUrl,
        private ?LoggerInterface $hydratorLogger = null,
    ) {
    }

    public function hydrate(string $template, array $data): string
    {
        try {
            $response = $this->httpClient->request('POST', rtrim($this->goServiceUrl, '/').'/generate', [
                'json' => [
                    'template' => $template,
                    'data' => $data,
                ],
            ]);
        } catch (TransportExceptionInterface $exception) {
            $this->hydratorLogger?->error('Go hydrator transport error', [
                'exception' => $exception,
                'go_service_url' => $this->goServiceUrl,
            ]);

            throw new GoExcelHydratorException('Ошибка подключения к GO сервису.', previous: $exception);
        }

        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);
        $payload = json_decode($content, true);

        if ($statusCode >= 400) {
            $errorMessage = is_array($payload) ? ($payload['message'] ?? $content) : $content;
            $message = is_string($errorMessage) && '' !== $errorMessage ? $errorMessage : 'Неизвестная ошибка GO сервиса.';

            $this->hydratorLogger?->error('Go hydrator responded with error', [
                'status_code' => $statusCode,
                'error' => $message,
                'template' => $template,
            ]);

            throw new GoExcelHydratorException(sprintf('Ошибка наполнения excel: %s', $message));
        }

        if (!is_array($payload) || !isset($payload['filename']) || !is_string($payload['filename']) || '' === $payload['filename']) {
            throw new GoExcelHydratorException("Некорректный ответ от GO сервиса: не передан параметр 'filename'.");
        }

        return $payload['filename'];
    }
}
