<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AttemptController extends AbstractController
{
    #[Route('/attempt', name: 'app_attempt')]
    public function index(): Response
    {
        return $this->render('attempt/index.html.twig', [
            'controller_name' => 'AttemptController',
        ]);
    }
}
