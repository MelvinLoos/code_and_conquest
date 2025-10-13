<?php

namespace App\Controller\Api;

use App\Entity\PlayerCharacter;
use App\Service\ChallengeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/missions')]
/** @OA\Tag(name="Missions") */
class MissionController extends AbstractController
{
    private const ENERGY_COST_TO_REFRESH = 10;
    private const COOLDOWN_MINUTES = 5;

    #[Route('', name: 'api_get_missions', methods: ['GET'])]
    /** @OA\Response(response=200, description="Returns the character's current mission board.") @OA\Security(name="Bearer") */
    public function getMissionBoard(#[CurrentUser] PlayerCharacter $character, ChallengeService $challengeService, EntityManagerInterface $em): JsonResponse
    {
        $missionBoard = $character->getMissionBoard();

        if (empty($missionBoard)) {
            // Narrative check: is character on cooldown from a SUCCESSFUL mission?
            $lastCompletion = $character->getLastChallengeCompletedAt();
            if ($lastCompletion && ($lastCompletion->getTimestamp() + (self::COOLDOWN_MINUTES * 60)) > time()) {
                $timeLeft = ($lastCompletion->getTimestamp() + (self::COOLDOWN_MINUTES * 60)) - time();
                return $this->json(['message' => "System lockout. Your rig is cooling down. New missions available in " . ceil($timeLeft) . " seconds."], Response::HTTP_TOO_MANY_REQUESTS);
            }

            // Energy check
            if ($character->getEnergy() < self::ENERGY_COST_TO_REFRESH) {
                return $this->json(['message' => 'Insufficient energy to search for new missions. Your Netrunner must rest.'], Response::HTTP_TOO_MANY_REQUESTS);
            }
            
            $missions = $challengeService->generateMissionBoard();
            $character->setMissionBoard($missions);
            $character->setEnergy($character->getEnergy() - self::ENERGY_COST_TO_REFRESH);
            $em->flush();
            
            $missionBoard = $missions;
        }

        // Return only the 'puzzle' part of each mission
        $publicBoard = array_map(fn($mission) => $mission['puzzle'], $missionBoard);

        return $this->json($publicBoard);
    }
    
    #[Route('/{id}/accept', name: 'api_accept_mission', methods: ['POST'])]
    /** @OA\Response(response=200, description="Accepts a mission from the board.") @OA\Security(name="Bearer") */
    public function acceptMission(string $id, #[CurrentUser] PlayerCharacter $character, EntityManagerInterface $em): JsonResponse
    {
        if ($character->getActiveMission()) {
            return $this->json(['message' => 'You already have an active mission.'], Response::HTTP_CONFLICT);
        }

        $missionBoard = $character->getMissionBoard();
        $missionToAccept = null;
        foreach ($missionBoard as $mission) {
            if ($mission['puzzle']['id'] === $id) {
                $missionToAccept = $mission;
                break;
            }
        }

        if (!$missionToAccept) {
            return $this->json(['message' => 'Mission not found on your board.'], Response::HTTP_NOT_FOUND);
        }

        $character->setActiveMission($missionToAccept);
        $character->setMissionBoard([]); // Clear the board once a mission is accepted
        $em->flush();

        return $this->json(['message' => 'Mission accepted. Details loaded into active memory.', 'activeMission' => $missionToAccept['puzzle']]);
    }


    #[Route('/solve', name: 'api_solve_mission', methods: ['POST'])]
    /** @OA\RequestBody(description="The solution to the active mission.", required=true, @OA\JsonContent(type="object", @OA\Property(property="solution", type="any", example="The solution string or number"))) @OA\Response(response=200, description="Correct solution.") @OA\Security(name="Bearer") */
    public function solveMission(#[CurrentUser] PlayerCharacter $character, Request $request, EntityManagerInterface $em, RateLimiterFactory $apiEndpointsLimiter): JsonResponse
    {
        $limiter = $apiEndpointsLimiter->create($character->getApiKey());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'ICE detected unusual activity. Your connection has been temporarily throttled.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $activeMission = $character->getActiveMission();
        if (!$activeMission) {
            return $this->json(['message' => 'No active mission to solve.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $solution = $data['solution'] ?? null;
        
        $difficulty = $activeMission['puzzle']['difficulty'];
        
        if ($solution === $activeMission['solution']) {
            $rewardGold = rand(50, 150) * (match($difficulty) {'easy' => 1, 'medium' => 2, 'hard' => 4});
            $victoryToken = 'VT_' . uniqid();

            $character->setGold($character->getGold() + $rewardGold);
            $character->setActiveMission(null);
            $character->setLastChallengeCompletedAt(new \DateTimeImmutable());
            $em->flush();

            return $this->json([
                'message' => 'Mission successful! Data acquired.',
                'reward' => [
                    'gold' => $rewardGold,
                    'victoryToken' => $victoryToken,
                ]
            ]);
        }
        
        $energyPenalty = 10 * (match($difficulty) {'easy' => 1, 'medium' => 2, 'hard' => 3});
        $character->setEnergy($character->getEnergy() - $energyPenalty);
        $em->flush();

        return $this->json(['message' => 'Trace detected! The solution was incorrect. Counter-intrusion measures have drained your energy.'], Response::HTTP_BAD_REQUEST);
    }
}
