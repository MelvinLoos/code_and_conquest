<?php

namespace App\Service;

class ChallengeService
{
    private const CHALLENGE_TYPES = ['sum_evens', 'decrypt_cipher', 'find_file'];

    public function generateMissionBoard(): array
    {
        // Generate 3 unique missions for the board
        return [
            $this->generateMission('easy'),
            $this->generateMission('medium'),
            $this->generateMission('hard'),
        ];
    }

    private function generateMission(string $difficulty): array
    {
        $type = self::CHALLENGE_TYPES[array_rand(self::CHALLENGE_TYPES)];

        $challengeData = match ($type) {
            'sum_evens' => $this->generateSumEvensChallenge($difficulty),
            'decrypt_cipher' => $this->generateDecryptCipherChallenge($difficulty),
            'find_file' => $this->generateFindFileChallenge($difficulty),
            default => $this->generateSumEvensChallenge($difficulty),
        };
        
        $challengeData['puzzle']['difficulty'] = $difficulty;
        $challengeData['puzzle']['rewardHint'] = $this->getRewardHint($difficulty);

        return $challengeData;
    }

    private function getRewardHint(string $difficulty): array {
        return match ($difficulty) {
            'easy' => ['gold' => '50-100', 'xp' => 'low'],
            'medium' => ['gold' => '100-200', 'xp' => 'medium'],
            'hard' => ['gold' => '200-400', 'xp' => 'high'],
        };
    }

    private function generateSumEvensChallenge(string $difficulty): array
    {
        $count = match($difficulty) {
            'easy' => 5,
            'medium' => 10,
            'hard' => 20,
        };

        $numbers = [];
        for ($i = 0; $i < $count; $i++) {
            $numbers[] = rand(1, 100);
        }

        $puzzle = [
            'id' => uniqid('ch_sum_', true),
            'type' => 'sum_evens',
            'title' => 'Data Stream Anomaly',
            'instructions' => 'Analyze the incoming data stream and sum all the even numbers to find the decryption key.',
            'payload' => $numbers,
        ];

        $solution = array_sum(array_filter($numbers, fn($n) => $n % 2 === 0));

        return ['puzzle' => $puzzle, 'solution' => $solution];
    }

    private function generateDecryptCipherChallenge(string $difficulty): array
    {
        $messages = ["ACCESS GRANTED", "SECURITY BYPASSED", "ADMIN OVERRIDE"];
        $originalMessage = $messages[array_rand($messages)];
        
        $shift = match($difficulty) {
            'easy' => rand(3, 5),
            'medium' => rand(6, 10),
            'hard' => rand(11, 20),
        };

        $encryptedMessage = "";

        for ($i = 0; $i < strlen($originalMessage); $i++) {
            $char = $originalMessage[$i];
            if (ctype_alpha($char)) {
                $encryptedMessage .= chr((ord($char) - 65 + $shift) % 26 + 65);
            } else {
                $encryptedMessage .= $char;
            }
        }

        $puzzle = [
            'id' => uniqid('ch_dec_', true),
            'type' => 'decrypt_cipher',
            'title' => 'Encrypted Comm-Link',
            'instructions' => 'Intercepted an encrypted message. It appears to be a simple Caesar cipher. Decrypt it to reveal the password.',
            'payload' => [
                'encrypted_message' => $encryptedMessage,
                'shift_key' => $shift,
            ],
        ];

        return ['puzzle' => $puzzle, 'solution' => $originalMessage];
    }

    private function generateFindFileChallenge(string $difficulty): array
    {
        $targetFile = "secret_intel.dat";
        
        $structure = match($difficulty) {
            'easy' => [
                'solution' => 'root/corp_data/' . $targetFile,
                'directory' => [
                    'name' => 'root', 'type' => 'folder', 'children' => [
                        ['name' => 'system.log', 'type' => 'file'],
                        ['name' => 'corp_data', 'type' => 'folder', 'children' => [
                            ['name' => 'quarterly_report.pdf', 'type' => 'file'],
                            ['name' => $targetFile, 'type' => 'file'],
                        ]],
                    ]
                ]
            ],
            'medium' => [
                'solution' => 'root/corp_data/archives/' . $targetFile,
                'directory' => [
                    'name' => 'root', 'type' => 'folder', 'children' => [
                        ['name' => 'system.log', 'type' => 'file'],
                        ['name' => 'corp_data', 'type' => 'folder', 'children' => [
                            ['name' => 'quarterly_report.pdf', 'type' => 'file'],
                            ['name' => 'archives', 'type' => 'folder', 'children' => [
                                ['name' => $targetFile, 'type' => 'file'],
                            ]],
                        ]],
                    ]
                ]
            ],
            'hard' => [
                'solution' => 'root/corp_data/archives/2077_Q4/' . $targetFile,
                'directory' => [
                    'name' => 'root', 'type' => 'folder', 'children' => [
                        ['name' => 'user_profiles', 'type' => 'folder', 'children' => []],
                        ['name' => 'corp_data', 'type' => 'folder', 'children' => [
                            ['name' => 'archives', 'type' => 'folder', 'children' => [
                                ['name' => '2077_Q3', 'type' => 'folder', 'children' => []],
                                ['name' => '2077_Q4', 'type' => 'folder', 'children' => [
                                    ['name' => $targetFile, 'type' => 'file']
                                ]],
                            ]],
                        ]],
                    ]
                ]
            ],
        };

        $puzzle = [
            'id' => uniqid('ch_find_', true),
            'type' => 'find_file',
            'title' => 'Corporate Data Fortress',
            'instructions' => 'Infiltrate the corporate file server and find the full path to the file named "' . $targetFile . '". The path should start with "root". Example: root/some_folder/file.txt',
            'payload' => $structure['directory'],
        ];

        return ['puzzle' => $puzzle, 'solution' => $structure['solution']];
    }
}