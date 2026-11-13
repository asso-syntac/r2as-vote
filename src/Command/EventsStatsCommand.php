<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:events:stats', description: 'Vue d\'ensemble des events, ou statistiques détaillées d\'un event donné.')]
class EventsStatsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('uuid', InputArgument::OPTIONAL, 'UUID admin de l\'event (sans : vue d\'ensemble de tous les events)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid');

        if ($uuid === null) {
            return $this->overview($io);
        }

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

        $io->section('Participation');
        $io->definitionList(
            ['Votants inscrits' => $totalVoters],
            ['Ont voté (au moins 1 proposition)' => $votedVoters],
            ['Taux de participation' => self::rate($votedVoters, $totalVoters)],
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

    private function overview(SymfonyStyle $io): int
    {
        $events = $this->connection->fetchAllAssociative(
            'SELECT e.id, e.name, e.state, e.created_at,
                    (SELECT COUNT(*) FROM proposal p WHERE p.event_id_id = e.id) AS proposals,
                    (SELECT COUNT(*) FROM users u WHERE u.event_id_id = e.id) AS voters,
                    (SELECT COALESCE(SUM(u.factor), 0) FROM users u WHERE u.event_id_id = e.id) AS voices,
                    (SELECT COUNT(DISTINCT r.user_id_id) FROM response_type1 r WHERE r.event_id_id = e.id) AS voted
             FROM events e
             ORDER BY e.created_at, e.id'
        );

        if (!$events) {
            $io->warning('Aucun event.');
            return Command::SUCCESS;
        }

        $rows = [];
        $totals = ['proposals' => 0, 'voters' => 0, 'voices' => 0, 'voted' => 0];
        foreach ($events as $e) {
            foreach ($totals as $k => $_) {
                $totals[$k] += (int) $e[$k];
            }
            $rows[] = [
                $e['id'],
                mb_strimwidth($e['name'], 0, 40, '…'),
                $e['state'] ? 'ouvert' : 'clos',
                substr((string) $e['created_at'], 0, 16),
                $e['proposals'],
                $e['voters'],
                $e['voices'],
                $e['voted'],
                self::rate((int) $e['voted'], (int) $e['voters']),
            ];
        }
        $rows[] = new TableSeparator();
        $rows[] = [
            '', sprintf('Total (%d events)', count($events)), '', '',
            $totals['proposals'], $totals['voters'], $totals['voices'], $totals['voted'],
            self::rate($totals['voted'], $totals['voters']),
        ];

        $io->table(
            ['ID', 'Nom', 'État', 'Créé le', 'Propositions', 'Votants', 'Voix', 'Ont voté', 'Participation'],
            $rows
        );
        $io->comment('Détail d\'un event : app:events:stats <uuid> (uuid via app:events:list)');

        return Command::SUCCESS;
    }

    private static function rate(int $voted, int $total): string
    {
        return ($total > 0 ? round($voted / $total * 100, 1) : 0) . ' %';
    }
}
