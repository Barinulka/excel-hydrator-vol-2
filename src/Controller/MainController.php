<?php

namespace App\Controller;

use App\Service\MainService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(MainService $service): Response
    {
        $page = $service->getMainData();

        if (!$page) {
            throw $this->createNotFoundException();
        }

        return $this->render('main/index.html.twig', [
            'page' => $page,
        ]);
    }
}
