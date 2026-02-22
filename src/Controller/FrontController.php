<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class FrontController extends AbstractController
{
    #[Route(path: '/front/{slug}', name: 'front')]
    public function index(string $slug, \Twig\Environment $environment): Response
    {
        $view = "@front/$slug.html.twig";

        if (!$environment->getLoader()->exists($view)) {
            throw $this->createNotFoundException('The page does not exist');
        }

        return $this->render($view);
    }
}
