<?php

namespace App\Service;

class CharacterStatsService
{
    private const CLASSES = [
        'netrunner' => [
            'name' => 'Netrunner',
            'description' => 'A master of code and systems. Excels in `sysKnowledge`, making them adept at cracking complex logical puzzles.',
            'template' => [
                'sysKnowledge' => [14, 18], // High
                'analytics'    => [12, 16], // Medium
                'interface'    => [10, 14], // Medium
                'secOps'       => [8, 12],   // Low
                'peopleSkills' => [8, 12],   // Low
            ]
        ],
        'data_miner' => [
            'name' => 'Data Miner',
            'description' => 'A digital scavenger who sees patterns in noise. High `analytics` helps them sift through large datasets efficiently.',
            'template' => [
                'analytics'    => [14, 18], // High
                'secOps'       => [12, 16], // Medium
                'sysKnowledge' => [10, 14], // Medium
                'interface'    => [8, 12],   // Low
                'peopleSkills' => [8, 12],   // Low
            ]
        ],
        'sleuth' => [
            'name' => 'Sleuth',
            'description' => 'A social engineer who exploits the human element. Strong `peopleSkills` can bypass technical security entirely.',
            'template' => [
                'peopleSkills' => [14, 18], // High
                'interface'    => [12, 16], // Medium
                'analytics'    => [10, 14], // Medium
                'sysKnowledge' => [8, 12],   // Low
                'secOps'       => [8, 12],   // Low
            ]
        ]
    ];

    public function generateStatsForClass(string $classIdentifier): array
    {
        $template = self::CLASSES[$classIdentifier]['template'] ?? self::CLASSES['netrunner']['template'];

        $stats = [];
        foreach ($template as $stat => $range) {
            $stats[$stat] = random_int($range[0], $range[1]);
        }
        return $stats;
    }

    public static function getClasses(): array
    {
        $classChoices = [];
        foreach (self::CLASSES as $identifier => $class) {
            $classChoices[$class['name']] = $identifier;
        }
        return $classChoices;
    }

    public function getAllClassesWithDetails(): array
    {
        return self::CLASSES;
    }
}