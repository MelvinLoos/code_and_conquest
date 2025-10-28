<?php

namespace App\Controller\Api;

use App\Entity\PlayerCharacter;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

#[Route('/api')]
class CharacterController extends AbstractController
{
    #[Route('/character', name: 'api_get_character', methods: ['GET'])]
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
    #[OA\Response(response: 200, description: 'Character leveled up successfully.')]
    #[OA\Response(response: 400, description: 'Invalid or already used victory token.')]
    #[Security(name: "Bearer")]
    public function levelUp(#[CurrentUser] PlayerCharacter $character, Request $request, EntityManagerInterface $em): JsonResponse
    {
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
        $redeemedTokens[] = $token;
        $character->setRedeemedTokens($redeemedTokens);

        $em->flush();

        return $this->json($character, 200, [], ['groups' => 'character:read']);
    }
}