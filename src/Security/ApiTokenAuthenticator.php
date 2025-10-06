<?php

namespace App\Security;

use App\Repository\CharacterRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private $characterRepository;

    public function __construct(CharacterRepository $characterRepository)
    {
        $this->characterRepository = $characterRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = str_replace('Bearer ', '', $request->headers->get('Authorization'));
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No Access Key provided');
        }

        $character = $this->characterRepository->findOneBy(['apiKey' => $apiToken]);
        if (null === $character) {
            throw new CustomUserMessageAuthenticationException('Invalid Access Key');
        }

        return new SelfValidatingPassport(new UserBadge($character->getApiKey()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // let the request continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
