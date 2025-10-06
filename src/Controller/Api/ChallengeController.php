<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Service\ChallengeService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

#[Route('/api/challenge')]
#[OA\Tag(name: 'Challenge')]
class ChallengeController extends AbstractController
{
    private const ENERGY_COST_TO_REQUEST = 5;
    private const ENERGY_COST_ON_FAILURE = 10;
    private const COOLDOWN_MINUTES = 2;

    #[Route('', name: 'api_get_challenge', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns a new challenge if the character is eligible.'
    )]
    #[OA\Response(
        response: 429,
        description: 'Character is on cooldown or out of energy.'
    )]
    #[OA\Response(
        response: 409,
        description: 'Character already has an active challenge.'
    )]
    #[Security(name: "Bearer")]
    public function getChallenge(#[CurrentUser] Character $character, ChallengeService $challengeService, EntityManagerInterface $em): JsonResponse
    {
        if ($character->getActiveChallenge()) {
            return $this->json(['message' => 'Your Netrunner already has an active mission. Solve it before getting a new one.'], 409);
        }

        $lastCompletion = $character->getLastChallengeCompletedAt();
        if ($lastCompletion && ($lastCompletion->getTimestamp() + (self::COOLDOWN_MINUTES * 60)) > time()) {
            $timeLeft = ($lastCompletion->getTimestamp() + (self::COOLDOWN_MINUTES * 60)) - time();
            return $this->json(['message' => "System lockout. Your rig is cooling down. New missions available in " . ceil($timeLeft) . " seconds."], 429);
        }

        if ($character->getEnergy() < self::ENERGY_COST_TO_REQUEST) {
            return $this->json(['message' => 'Insufficient energy to start a new mission. Your Netrunner must rest.'], 429);
        }

        $challenge = $challengeService->generateChallenge();
        
        $character->setActiveChallenge($challenge);
        $character->setEnergy($character->getEnergy() - self::ENERGY_COST_TO_REQUEST);
        $em->flush();

        return $this->json($challenge['puzzle']);
    }

    #[Route('/solve', name: 'api_solve_challenge', methods: ['POST'])]
    #[OA\RequestBody(
        description: "The solution to the active challenge.",
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'solution', type: 'integer', example: 123)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Correct solution. Returns a reward.'
    )]
    #[OA\Response(
        response: 400,
        description: 'Incorrect solution or no active challenge.'
    )]
    #[OA\Security(name: "Bearer")]
    public function solveChallenge(#[CurrentUser] Character $character, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $activeChallenge = $character->getActiveChallenge();
        if (!$activeChallenge) {
            return $this->json(['message' => 'No active mission to solve.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $solution = $data['solution'] ?? null;

        if ($solution === $activeChallenge['solution']) {
            $rewardGold = rand(50, 150);
            $victoryToken = 'VT_' . uniqid();

            $character->setGold($character->getGold() + $rewardGold);
            $character->setActiveChallenge(null);
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
        
        $character->setEnergy($character->getEnergy() - self::ENERGY_COST_ON_FAILURE);
        $em->flush();

        return $this->json(['message' => 'Trace detected! The solution was incorrect. Counter-intrusion measures have drained your energy.'], 400);
    }
}