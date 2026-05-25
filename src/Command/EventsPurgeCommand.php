<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:events:purge', description: 'Supprime les events plus vieux que N jours, et tous les votants / propositions / votes associés.')]
class EventsPurgeCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('older-than-days', null, InputOption::VALUE_REQUIRED, 'Âge minimum en jours pour être supprimé', '730')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Ne supprime rien, affiche juste ce qui serait supprimé')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Ne demande pas de confirmation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = (int) $input->getOption('older-than-days');
        if ($days < 1) {
            $io->error('--older-than-days doit être >= 1');
            return Command::FAILURE;
        }

        $cutoff = new \DateTimeImmutable("-$days days");
        $io->section(sprintf('Cible : events créés avant %s (plus vieux que %d jours)', $cutoff->format('Y-m-d H:i'), $days));

        $eventIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM events WHERE created_at < :cutoff',
            ['cutoff' => $cutoff->format('Y-m-d H:i:s')]
        );

        if (!$eventIds) {
            $io->success('Aucun event à supprimer.');
            return Command::SUCCESS;
        }

        $placeholder = implode(',', array_fill(0, count($eventIds), '?'));
        $voterCount = $this->connection->fetchOne("SELECT COUNT(*) FROM users WHERE event_id_id IN ($placeholder)", $eventIds);
        $proposalCount = $this->connection->fetchOne("SELECT COUNT(*) FROM proposal WHERE event_id_id IN ($placeholder)", $eventIds);
        $voteCount = $this->connection->fetchOne("SELECT COUNT(*) FROM response_type1 WHERE event_id_id IN ($placeholder)", $eventIds);

        $io->table(['Type', 'À supprimer'], [
            ['Events', count($eventIds)],
            ['Votants', $voterCount],
            ['Propositions', $proposalCount],
            ['Votes', $voteCount],
        ]);

        if ($input->getOption('dry-run')) {
            $io->warning('Mode --dry-run : rien n\'a été supprimé.');
            return Command::SUCCESS;
        }

        if (!$input->getOption('force')) {
            if (!$io->confirm('Confirmer la suppression définitive ?', false)) {
                $io->warning('Annulé.');
                return Command::SUCCESS;
            }
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement("DELETE FROM response_type1 WHERE event_id_id IN ($placeholder)", $eventIds);
            $this->connection->executeStatement("DELETE FROM proposal WHERE event_id_id IN ($placeholder)", $eventIds);
            $this->connection->executeStatement("DELETE FROM users WHERE event_id_id IN ($placeholder)", $eventIds);
            $this->connection->executeStatement("DELETE FROM events WHERE id IN ($placeholder)", $eventIds);
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $io->error('Échec de la suppression : ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d event(s), %d votant(s), %d proposition(s), %d vote(s) supprimés.',
            count($eventIds), $voterCount, $proposalCount, $voteCount
        ));
        return Command::SUCCESS;
    }
}
