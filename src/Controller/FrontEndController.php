<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontEndController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/{reactRouting}', name: 'app_home_catchall', requirements: ['reactRouting' => '.+'])]
    public function index(): Response
    {
        // Sert le fichier Angular principal
        return $this->file('front/browser/index.html');
    }
}