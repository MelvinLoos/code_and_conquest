<?php

namespace App\Controller;

use App\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(CharacterRepository $characterRepository): Response
    {
        $characters = $characterRepository->findAll();

        return $this->render('dashboard/index.html.twig', [
            'characters' => $characters,
        ]);
    }
}