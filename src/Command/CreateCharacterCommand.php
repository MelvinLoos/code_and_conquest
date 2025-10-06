<?php

namespace App\Command;

use App\Entity\Character;
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

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The handle of the Netrunner.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $character = new Character();
        $character->setCharacterName($name);
        $character->setApiKey(bin2hex(random_bytes(30)));
        $character->setStats([
            'interface'    => random_int(10, 18), // Represents typing speed & efficiency
            'analytics'    => random_int(8, 15),  // Represents pattern recognition
            'sysKnowledge' => random_int(8, 15), // Represents technical knowledge
            'secOps'       => random_int(8, 15),    // Represents defensive skills & threat assessment
            'peopleSkills' => random_int(8, 15), // Represents collaboration and communication
        ]);

        $this->entityManager->persist($character);
        $this->entityManager->flush();

        $io->success('Netrunner "' . $name . '" is now online!');
        $io->writeln('Access Key: . ' . $character->getApiKey());

        return Command::SUCCESS;
    }
}