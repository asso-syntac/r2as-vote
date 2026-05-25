<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:events:stats', description: 'Statistiques détaillées pour un event donné.')]
class EventsStatsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('uuid', InputArgument::REQUIRED, 'UUID admin de l\'event');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid');

        $event = $this->connection->fetchAssociative(
            'SELECT id, name, description, mail, state, created_at FROM events WHERE uuid = :uuid',
            ['uuid' => $uuid]
        );
        if (!$event) {
            $io->error("Event $uuid introuvable.");
            return Command::FAILURE;
        }
        $eventId = (int) $event['id'];

        $io->section('Event');
        $io->definitionList(
            ['Nom' => $event['name']],
            ['Description' => $event['description'] ?: '(aucune)'],
            ['Mail admin' => $event['mail']],
            ['État' => $event['state'] ? 'ouvert' : 'clos'],
            ['Créé le' => substr((string) $event['created_at'], 0, 16)],
        );

        $totalVoters = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM users WHERE event_id_id = :id',
            ['id' => $eventId]
        );
        $votedVoters = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT user_id_id) FROM response_type1 WHERE event_id_id = :id',
            ['id' => $eventId]
        );
        $participationRate = $totalVoters > 0 ? round($votedVoters / $totalVoters * 100, 1) : 0;

        $io->section('Participation');
        $io->definitionList(
            ['Votants inscrits' => $totalVoters],
            ['Ont voté (au moins 1 proposition)' => $votedVoters],
            ['Taux de participation' => $participationRate . ' %'],
        );

        $propositions = $this->connection->fetchAllAssociative(
            'SELECT p.id, p.name,
                    COALESCE(SUM(r.positive), 0) AS pour,
                    COALESCE(SUM(r.negative), 0) AS contre,
                    COALESCE(SUM(r.abstention), 0) AS abstention,
                    COUNT(DISTINCT r.user_id_id) AS participants
             FROM proposal p
             LEFT JOIN response_type1 r ON r.proposal_id_id = p.id
             WHERE p.event_id_id = :id
             GROUP BY p.id, p.name
             ORDER BY p.id',
            ['id' => $eventId]
        );

        if (!$propositions) {
            $io->warning('Aucune proposition pour cet event.');
            return Command::SUCCESS;
        }

        $io->section('Résultats par proposition');
        $rows = [];
        foreach ($propositions as $p) {
            $total = $p['pour'] + $p['contre'] + $p['abstention'];
            $rows[] = [
                $p['id'],
                mb_strimwidth($p['name'], 0, 50, '…'),
                $p['pour'],
                $p['contre'],
                $p['abstention'],
                $total,
                $p['participants'],
            ];
        }
        $io->table(
            ['ID', 'Proposition', 'Pour', 'Contre', 'Abstention', 'Total voix', 'Votants'],
            $rows
        );

        return Command::SUCCESS;
    }
}
