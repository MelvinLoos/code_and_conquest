<?php

namespace App\Service;

class ChallengeService
{
    /**
     * Generates a new, random challenge.
     * Returns an array containing the public-facing 'puzzle'
     * and the private 'solution'.
     */
    public function generateChallenge(): array
    {
        // For now, we'll create a simple challenge.
        // This can be expanded later with more types.
        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = rand(1, 100);
        }

        $puzzle = [
            'id' => uniqid('ch_', true),
            'type' => 'sum_evens',
            'title' => 'Data Stream Anomaly',
            'instructions' => 'Analyze the incoming data stream and sum all the even numbers to find the decryption key.',
            'payload' => $numbers,
        ];

        $solution = array_sum(array_filter($numbers, fn($n) => $n % 2 === 0));

        return [
            'puzzle' => $puzzle,
            'solution' => $solution,
        ];
    }
}