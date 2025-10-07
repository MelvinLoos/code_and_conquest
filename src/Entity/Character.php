<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
class Character implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $apiKey = null;

    #[ORM\Column(length: 255)]
    #[Groups(['character:read'])]
    private ?string $characterName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['character:read'])]
    private ?string $characterClass = null;

    #[ORM\Column]
    #[Groups(['character:read'])]
    private ?int $level = null;

    #[ORM\Column]
    #[Groups(['character:read'])]
    private ?int $gold = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['character:read'])]
    private array $stats = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['character:read'])]
    private array $inventory = [];

    #[ORM\Column]
    #[Groups(['character:read'])]
    private ?int $energy = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['character:read'])]
    private ?array $missionBoard = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['character:read'])]
    private ?array $activeMission = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['character:read'])]
    private ?\DateTimeImmutable $lastChallengeCompletedAt = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['character:read'])]
    private array $redeemedTokens = [];

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    public function __construct()
    {
        $this->level = 1;
        $this->gold = 10;
        $this->energy = 100;
        $this->stats = [];
        $this->inventory = [];
        $this->redeemedTokens = [];
        $this->missionBoard = [];
    }

    // --- GETTERS AND SETTERS ---
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }
    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }
    public function setCharacterName(string $characterName): self
    {
        $this->characterName = $characterName;
        return $this;
    }
    public function getCharacterClass(): ?string
    {
        return $this->characterClass;
    }
    public function setCharacterClass(string $characterClass): self
    {
        $this->characterClass = $characterClass;
        return $this;
    }
    public function getLevel(): ?int
    {
        return $this->level;
    }
    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }
    public function getGold(): ?int
    {
        return $this->gold;
    }
    public function setGold(int $gold): self
    {
        $this->gold = $gold;
        return $this;
    }
    public function getStats(): array
    {
        return $this->stats;
    }
    public function setStats(array $stats): self
    {
        $this->stats = $stats;
        return $this;
    }
    public function getInventory(): array
    {
        return $this->inventory;
    }
    public function setInventory(array $inventory): self
    {
        $this->inventory = $inventory;
        return $this;
    }
    public function getEnergy(): ?int
    {
        return $this->energy;
    }
    public function setEnergy(int $energy): self
    {
        $this->energy = $energy;
        return $this;
    }
    public function getMissionBoard(): ?array
    {
        return $this->missionBoard;
    }
    public function setMissionBoard(?array $missionBoard): self
    {
        $this->missionBoard = $missionBoard;
        return $this;
    }
    public function getActiveMission(): ?array
    {
        return $this->activeMission;
    }
    public function setActiveMission(?array $activeMission): self
    {
        $this->activeMission = $activeMission;
        return $this;
    }
    public function getLastChallengeCompletedAt(): ?\DateTimeImmutable
    {
        return $this->lastChallengeCompletedAt;
    }
    public function setLastChallengeCompletedAt(?\DateTimeImmutable $lastChallengeCompletedAt): self
    {
        $this->lastChallengeCompletedAt = $lastChallengeCompletedAt;
        return $this;
    }
    public function getRedeemedTokens(): array
    {
        return $this->redeemedTokens;
    }
    public function setRedeemedTokens(array $redeemedTokens): self
    {
        $this->redeemedTokens = $redeemedTokens;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    // --- UserInterface methods ---
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }
    public function eraseCredentials(): void
    {
    }
    public function getUserIdentifier(): string
    {
        return (string) $this->apiKey;
    }
    public function getPassword(): ?string
    {
        return null;
    }
}
