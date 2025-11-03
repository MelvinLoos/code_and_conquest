<?php

namespace App\Command;

use App\Entity\PlayerCharacter;
use App\Service\CharacterStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-character',
    description: 'Creates a new character and generates an API key.',
)]
class CreateCharacterCommand extends Command
{
    private $entityManager;
    private $statsService;

    public function __construct(EntityManagerInterface $entityManager, CharacterStatsService $statsService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->statsService = $statsService;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The handle of the Netrunner.');
        $this->addArgument('class', InputArgument::REQUIRED, 'The class of the Netrunner (netrunner, data_miner, sleuth).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $class = $input->getArgument('class');
        
        $validClasses = array_values(CharacterStatsService::getClasses());
        if (!in_array($class, $validClasses)) {
            $io->error('Invalid class provided. Valid classes are: ' . implode(', ', $validClasses));
            return Command::FAILURE;
        }

        $character = new PlayerCharacter();
        $character->setCharacterName($name);
        $character->setCharacterClass($class);
        $character->setEmail('test@glr.nl'); // Set a test email for CLI-created characters
        $character->setApiKey(bin2hex(random_bytes(30)));
        $character->setStats($this->statsService->generateStatsForClass($class));

        $this->entityManager->persist($character);
        $this->entityManager->flush();

        $io->success('Netrunner "' . $name . '" is now online!');
        $io->writeln('Access Key: ' . $character->getApiKey());

        return Command::SUCCESS;
    }
}