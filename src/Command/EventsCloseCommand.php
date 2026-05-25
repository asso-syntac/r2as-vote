<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:events:close', description: 'Clôt un event (ou le rouvre avec --reopen).')]
class EventsCloseCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('uuid', InputArgument::REQUIRED, 'UUID admin de l\'event')
            ->addOption('reopen', null, InputOption::VALUE_NONE, 'Rouvre l\'event au lieu de le clore')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid');
        $reopen = $input->getOption('reopen');

        $event = $this->connection->fetchAssociative(
            'SELECT id, name, state FROM events WHERE uuid = :uuid',
            ['uuid' => $uuid]
        );
        if (!$event) {
            $io->error("Event $uuid introuvable.");
            return Command::FAILURE;
        }

        $targetState = $reopen;
        if ((bool) $event['state'] === $targetState) {
            $io->warning(sprintf(
                'Event « %s » est déjà %s. Rien à faire.',
                $event['name'],
                $targetState ? 'ouvert' : 'clos'
            ));
            return Command::SUCCESS;
        }

        $this->connection->executeStatement(
            'UPDATE events SET state = :state WHERE id = :id',
            ['state' => $targetState, 'id' => $event['id']],
            ['state' => \PDO::PARAM_BOOL]
        );

        $io->success(sprintf(
            'Event « %s » est maintenant %s.',
            $event['name'],
            $targetState ? 'ouvert' : 'clos'
        ));
        return Command::SUCCESS;
    }
}
