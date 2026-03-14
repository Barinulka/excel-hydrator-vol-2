<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BaseAbstractController extends AbstractController
{
    protected function getAuthorizedUser(): User
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    protected function isTurboFrameRequest(Request $request, string $frameId): bool
    {
        return $frameId === $request->headers->get('Turbo-Frame');
    }

    protected function renderFrameOrPage(
        Request $request,
        string $pageTemplate,
        string $frameTemplate,
        array $context,
        string $frameId,
    ): Response {
        if ($this->isTurboFrameRequest($request, $frameId)) {
            return $this->render($frameTemplate, $context);
        }

        return $this->render($pageTemplate, $context);
    }
}
