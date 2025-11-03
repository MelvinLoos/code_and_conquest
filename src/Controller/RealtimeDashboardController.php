<?php

namespace App\Controller;

use App\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class RealtimeDashboardController extends AbstractController
{
    #[Route('/dashboard/stream', name: 'dashboard_stream')]
    public function streamUpdates(CharacterRepository $characterRepository): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Nginx buffering disable

        $response->setCallback(function () use ($characterRepository) {
            $lastDataHash = '';
            
            while (true) {
                $data = $this->getLeaderboardData($characterRepository);
                $currentDataHash = md5(json_encode($data));
                
                // Only send update if data has changed
                if ($currentDataHash !== $lastDataHash) {
                    echo "event: leaderboard-update\n";
                    echo "data: " . json_encode($data) . "\n\n";
                    
                    $lastDataHash = $currentDataHash;
                    
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
                
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }
                
                sleep(2); // Check every 2 seconds
            }
        });

        return $response;
    }

    #[Route('/api/dashboard/data', name: 'api_dashboard_data', methods: ['GET'])]
    public function getDashboardData(CharacterRepository $characterRepository): Response
    {
        $data = $this->getLeaderboardData($characterRepository);
        return $this->json($data);
    }

    private function getLeaderboardData(CharacterRepository $characterRepository): array
    {
        $characters = $characterRepository->findAll();

        // Sort for "Challenges Completed" leaderboard in PHP
        usort($characters, fn($a, $b) => count($b->getRedeemedTokens()) <=> count($a->getRedeemedTokens()));
        $byEfficiency = array_slice($characters, 0, 10);

        $byReputation = $characterRepository->findBy([], ['level' => 'DESC'], 10);
        $byWealth = $characterRepository->findBy([], ['gold' => 'DESC'], 10);

        return [
            'byReputation' => array_map(fn($char) => [
                'name' => $char->getCharacterName(),
                'level' => $char->getLevel(),
                'characterClass' => $char->getCharacterClass()
            ], $byReputation),
            'byWealth' => array_map(fn($char) => [
                'name' => $char->getCharacterName(),
                'gold' => $char->getGold(),
                'characterClass' => $char->getCharacterClass()
            ], $byWealth),
            'byEfficiency' => array_map(fn($char) => [
                'name' => $char->getCharacterName(),
                'missions' => count($char->getRedeemedTokens()),
                'characterClass' => $char->getCharacterClass()
            ], $byEfficiency),
            'timestamp' => time()
        ];
    }
}