<?php

namespace App\Command;

use App\Service\EventPointsDistributor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:distribute-event-points',
    description: 'Attribue les points aux participants des événements terminés.',
)]
class DistributeEventPointsCommand extends Command
{
    public function __construct(private EventPointsDistributor $distributor)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->distributor->distributePoints();

        $output->writeln("✅ Points attribués pour $count événement(s) terminé(s).");

        return Command::SUCCESS;
    }
}
