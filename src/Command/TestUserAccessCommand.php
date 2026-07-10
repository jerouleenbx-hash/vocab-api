<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'TestUserAccess',
    description: 'Add a short description for your command',
)]
class TestUserAccessCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

  protected function execute(InputInterface $input, OutputInterface $output): int
{
    $users = $this->entityManager->getRepository(User::class)->findAll();

    if (empty($users)) {
        $output->writeln('Aucun utilisateur trouvé.');
    } else {
        $output->writeln(sprintf('%d utilisateurs trouvés.', count($users)));
    }

    return Command::SUCCESS;
}
}
