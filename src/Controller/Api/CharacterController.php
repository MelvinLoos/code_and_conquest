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

        if (!$token || !str_starts_with($token, 'VT_')) {
            return $this->json(['message' => 'Invalid token format.'], 400);
        }

        $redeemedTokens = $character->getRedeemedTokens();
        if (in_array($token, $redeemedTokens)) {
            return $this->json(['message' => 'This token has already been redeemed.'], 400);
        }

        $character->setLevel($character->getLevel() + 1);
        $redeemedTokens[] = $token;
        $character->setRedeemedTokens($redeemedTokens);

        $em->flush();

        return $this->json($character, 200, [], ['groups' => 'character:read']);
    }
}