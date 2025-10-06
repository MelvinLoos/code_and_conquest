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

        // Sort for "Challenges Completed" leaderboard in PHP
        usort($characters, fn($a, $b) => count($b->getRedeemedTokens()) <=> count($a->getRedeemedTokens()));
        $byEfficiency = array_slice($characters, 0, 10);

        return $this->render('dashboard/index.html.twig', [
            'byReputation' => $characterRepository->findBy([], ['level' => 'DESC'], 10),
            'byWealth' => $characterRepository->findBy([], ['gold' => 'DESC'], 10),
            'byEfficiency' => $byEfficiency,
        ]);
    }
}