<?php

namespace App\Service;

use App\Entity\PlayerCharacter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingService
{
    private const VIOLATION_THRESHOLD_WARNING = 3;    // Hits before first warning
    private const VIOLATION_THRESHOLD_RESTRICT = 5;   // Hits before restriction
    private const VIOLATION_THRESHOLD_PENALTY = 8;    // Hits before penalty
    private const VIOLATION_THRESHOLD_BLOCK = 10;     // Hits before temporary block
    
    public function __construct(
        private RateLimiterFactory $apiNormalLimiter,
        private RateLimiterFactory $apiRestrictedLimiter,
        private RateLimiterFactory $apiPenaltyLimiter,
        private RateLimiterFactory $apiBlockedLimiter,
        private EntityManagerInterface $entityManager
    ) {}

    public function checkRateLimit(PlayerCharacter $character): ?array
    {
        $violations = $character->getRateLimitViolations() ?? 0;
        $limiter = match(true) {
            $violations >= self::VIOLATION_THRESHOLD_BLOCK => $this->apiBlockedLimiter,
            $violations >= self::VIOLATION_THRESHOLD_PENALTY => $this->apiPenaltyLimiter,
            $violations >= self::VIOLATION_THRESHOLD_RESTRICT => $this->apiRestrictedLimiter,
            default => $this->apiNormalLimiter
        };

        $limit = $limiter->create($character->getApiKey());
        
        if (false === $limit->consume(1)->isAccepted()) {
            // Increment violation count
            $character->setRateLimitViolations($violations + 1);
            
            // Apply energy penalty for repeated violations
            if ($violations >= self::VIOLATION_THRESHOLD_RESTRICT) {
                $energyPenalty = min(50, $character->getEnergy());
                $character->setEnergy($character->getEnergy() - $energyPenalty);
            }
            
            $this->entityManager->flush();

            return [
                'blocked' => true,
                'status' => Response::HTTP_TOO_MANY_REQUESTS,
                'message' => match(true) {
                    $violations >= self::VIOLATION_THRESHOLD_BLOCK =>
                        'ACCESS DENIED: Your connection has been temporarily blocked due to excessive API abuse. Try again in 1 hour.',
                    $violations >= self::VIOLATION_THRESHOLD_PENALTY =>
                        'SEVERE WARNING: Continued API abuse detected. Your access is severely restricted and energy has been drained.',
                    $violations >= self::VIOLATION_THRESHOLD_RESTRICT =>
                        'WARNING: Rate limit violations detected. Your access rate has been restricted and energy penalized.',
                    default =>
                        'Too many requests. Please slow down to avoid restrictions.'
                }
            ];
        }

        // Gradually reduce violations for good behavior (every 10 successful requests)
        if ($violations > 0 && rand(1, 10) === 1) { // Random chance to reduce violations on successful requests
            $character->setRateLimitViolations($violations - 1);
            $this->entityManager->flush();
        }

        return null;
    }
}