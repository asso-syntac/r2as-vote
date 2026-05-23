<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/healthz', name: 'healthz', methods: ['GET'])]
    public function healthz(Connection $connection): Response
    {
        try {
            $connection->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            return new Response('db down', 503, ['Content-Type' => 'text/plain']);
        }

        return new Response('ok', 200, ['Content-Type' => 'text/plain']);
    }
}
