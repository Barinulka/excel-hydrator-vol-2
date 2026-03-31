<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class BaseApiAbstractController extends BaseAbstractController
{
    protected function getJsonRequestData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    protected function jsonValidationErrorResponse(ConstraintViolationListInterface $errors): Response
    {
        $validationErrors = [];

        foreach ($errors as $error) {
            $field = $error->getPropertyPath();
            $validationErrors[$field][] = $error->getMessage();
        }

        return $this->json(['message' => 'Ошибка валидации', 'errors' => $validationErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function jsonNotFoundResponse(string $message): Response
    {
        return $this->json(['message' => $message], Response::HTTP_NOT_FOUND);
    }
}
