<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends AbstractController
{
    #[Route('/metrics-app', name: 'metrics_app', methods: ['GET'])]
    public function metrics(Connection $connection): Response
    {
        $row = $connection->fetchAssociative('
            SELECT
                (SELECT COUNT(*) FROM events) AS events_total,
                (SELECT COUNT(*) FROM events WHERE state = true) AS events_open,
                (SELECT COUNT(*) FROM events WHERE state = false) AS events_closed,
                (SELECT COUNT(*) FROM users) AS users_total,
                (SELECT COUNT(*) FROM proposal) AS proposals_total,
                (SELECT COUNT(*) FROM response_type1) AS votes_total
        ');

        $body = '';
        foreach ([
            'events_total'    => ['Total number of events ever created', $row['events_total']],
            'events_open'     => ['Number of events currently open', $row['events_open']],
            'events_closed'   => ['Number of events currently closed', $row['events_closed']],
            'users_total'     => ['Total number of voters registered', $row['users_total']],
            'proposals_total' => ['Total number of proposals', $row['proposals_total']],
            'votes_total'     => ['Total number of votes received', $row['votes_total']],
        ] as $name => [$help, $value]) {
            $metric = 'r2as_vote_' . $name;
            $body .= "# HELP $metric $help\n";
            $body .= "# TYPE $metric gauge\n";
            $body .= "$metric $value\n";
        }

        return new Response($body, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }
}
