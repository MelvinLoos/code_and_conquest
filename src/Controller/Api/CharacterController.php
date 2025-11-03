<?php

namespace App\Controller\Api;

use App\Entity\PlayerCharacter;
use App\Service\RateLimitingService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

#[Route('/api')]
#[OA\Tag(name: "Character")]
class CharacterController extends AbstractController
{
    #[Route('/character', name: 'api_get_character', methods: ['GET'])]
    #[OA\Response(
        response: 200, 
        description: 'Returns the character information.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Neo'),
                new OA\Property(property: 'characterClass', type: 'string', example: 'netrunner'),
                new OA\Property(property: 'level', type: 'integer', example: 5),
                new OA\Property(property: 'gold', type: 'integer', example: 2500),
                new OA\Property(property: 'energy', type: 'integer', example: 85),
                new OA\Property(property: 'sysKnowledge', type: 'integer', example: 16),
                new OA\Property(property: 'analytics', type: 'integer', example: 14),
                new OA\Property(property: 'interface', type: 'integer', example: 12),
                new OA\Property(property: 'secOps', type: 'integer', example: 10),
                new OA\Property(property: 'peopleSkills', type: 'integer', example: 8)
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication required - invalid or missing API token.')]
    #[Security(name: "Bearer")]
    public function getCharacter(#[CurrentUser] ?PlayerCharacter $character): JsonResponse
    {
        return $this->json($character, 200, [], ['groups' => 'character:read']);
    }

    #[Route('/character/levelup', name: 'api_character_levelup', methods: ['POST'])]
    #[OA\RequestBody(
        description: "The victory token earned from a successful challenge.",
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'victoryToken', type: 'string', example: "VT_abc123")
            ]
        )
    )]
    #[OA\Response(
        response: 200, 
        description: 'Character leveled up successfully.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Neo'),
                new OA\Property(property: 'characterClass', type: 'string', example: 'netrunner'),
                new OA\Property(property: 'level', type: 'integer', example: 6),
                new OA\Property(property: 'gold', type: 'integer', example: 2500),
                new OA\Property(property: 'energy', type: 'integer', example: 85)
            ]
        )
    )]
    #[OA\Response(
        response: 400, 
        description: 'Invalid or already used victory token.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Invalid or unearned victory token.')
            ]
        )
    )]
    #[OA\Response(
        response: 429, 
        description: 'Rate limit exceeded.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Rate limit exceeded. Too many requests.')
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication required - invalid or missing API token.')]
    #[Security(name: "Bearer")]
    public function levelUp(
        #[CurrentUser] PlayerCharacter $character,
        Request $request,
        EntityManagerInterface $em,
        RateLimitingService $rateLimiter
    ): JsonResponse
    {
        // Check rate limits with progressive penalties
        if ($rateLimit = $rateLimiter->checkRateLimit($character)) {
            return $this->json(['message' => $rateLimit['message']], $rateLimit['status']);
        }

        $data = json_decode($request->getContent(), true);
        $token = $data['victoryToken'] ?? null;

        if (!$token) {
            return $this->json(['message' => 'No token provided.'], 400);
        }

        $redeemedTokens = $character->getRedeemedTokens();
        
        // Check if token exists in the character's earned tokens array
        if (!in_array($token, $redeemedTokens)) {
            return $this->json(['message' => 'Invalid or unearned victory token.'], 400);
        }
        
        // Mark this token as used by removing it
        $redeemedTokens = array_values(array_diff($redeemedTokens, [$token]));
        $character->setRedeemedTokens($redeemedTokens);

        $character->setLevel($character->getLevel() + 1);

        $em->flush();

        return $this->json($character, 200, [], ['groups' => 'character:read']);
    }
}