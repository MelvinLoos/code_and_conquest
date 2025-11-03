<?php

namespace App\Service;

use App\Entity\PlayerCharacter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class RealtimeEventService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function dispatchCharacterUpdate(PlayerCharacter $character, string $updateType): void
    {
        $event = new GenericEvent($character, [
            'updateType' => $updateType,
            'characterId' => $character->getId(),
            'characterName' => $character->getCharacterName(),
            'level' => $character->getLevel(),
            'gold' => $character->getGold(),
            'completedMissions' => count($character->getRedeemedTokens())
        ]);

        $this->eventDispatcher->dispatch($event, 'character.updated');
    }

    public function dispatchLevelUp(PlayerCharacter $character): void
    {
        $this->dispatchCharacterUpdate($character, 'level_up');
    }

    public function dispatchMissionComplete(PlayerCharacter $character): void
    {
        $this->dispatchCharacterUpdate($character, 'mission_complete');
    }

    public function dispatchGoldChange(PlayerCharacter $character): void
    {
        $this->dispatchCharacterUpdate($character, 'gold_change');
    }
}