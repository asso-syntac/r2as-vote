<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:events:list', description: 'Liste les events avec leur lien d\'admin et stats associées.')]
class EventsListCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $publicUrl = 'https://vote.r2as.org'
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('open', null, InputOption::VALUE_NONE, 'Ne montrer que les events ouverts')
            ->addOption('closed', null, InputOption::VALUE_NONE, 'Ne montrer que les events clos')
            ->addOption('search', null, InputOption::VALUE_REQUIRED, 'Filtrer par nom (LIKE %search%)')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre max de résultats', '50')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $where = [];
        $params = [];
        if ($input->getOption('open')) {
            $where[] = 'e.state = true';
        }
        if ($input->getOption('closed')) {
            $where[] = 'e.state = false';
        }
        if ($search = $input->getOption('search')) {
            $where[] = 'e.name ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $sql = 'SELECT e.id, e.name, e.uuid, e.mail, e.state, e.created_at,
                       (SELECT COUNT(*) FROM users u WHERE u.event_id_id = e.id) AS voters,
                       (SELECT COUNT(*) FROM response_type1 r WHERE r.event_id_id = e.id) AS votes
                FROM events e';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.created_at DESC LIMIT :limit';
        $params['limit'] = (int) $input->getOption('limit');

        $rows = $this->connection->fetchAllAssociative($sql, $params, ['limit' => \PDO::PARAM_INT]);

        if (!$rows) {
            $io->warning('Aucun event ne correspond aux filtres.');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Nom', 'État', 'Créé le', 'Votants', 'Votes', 'Mail admin', 'Lien admin']);
        foreach ($rows as $r) {
            $table->addRow([
                $r['id'],
                mb_strimwidth($r['name'], 0, 40, '…'),
                $r['state'] ? 'ouvert' : 'clos',
                substr((string) $r['created_at'], 0, 16),
                $r['voters'],
                $r['votes'],
                $r['mail'],
                $this->publicUrl . '/param-vote/' . $r['uuid'],
            ]);
        }
        $table->render();

        $io->success(sprintf('%d event(s) affichés.', count($rows)));
        return Command::SUCCESS;
    }
}
