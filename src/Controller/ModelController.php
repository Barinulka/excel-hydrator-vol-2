<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/project', name:'app_model_')]
final class ModelController extends BaseAbstractController
{
    #[Route('/{projectShortId<[A-Za-z0-9]{10}>}/models/create', name: 'create')]
    public function create(string $projectShortId): Response
    {
        $user = $this->getAuthorizedUser();

        return $this->render('model/index.html.twig', [
            'controller_name' => 'ModelController',
        ]);
    }
}
