<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\KernelInterface;

class DebugController extends AbstractController
{
    #[Route('/_debug/sentry/exception', name: 'debug_sentry_exception')]
    public function exception(KernelInterface $kernel): Response
    {
        if ($kernel->getEnvironment() !== 'dev') {
            throw $this->createNotFoundException();
        }
        throw new \RuntimeException('Sentry dev test exception');
    }

    #[Route('/_debug/sentry/log', name: 'debug_sentry_log')]
    public function log(LoggerInterface $logger, KernelInterface $kernel): Response
    {
        if ($kernel->getEnvironment() !== 'dev') {
            throw $this->createNotFoundException();
        }
        $logger->error('Sentry dev test log', ['foo' => 'bar', 'time' => time()]);
        return new Response('Logged error to Monolog (Sentry handler will forward).');
    }

    #[Route('/_debug/sentry/logs-levels', name: 'debug_sentry_logs_levels')]
    public function logsLevels(LoggerInterface $logger, KernelInterface $kernel): Response
    {
        if ($kernel->getEnvironment() !== 'dev') {
            throw $this->createNotFoundException();
        }
        $ctx = ['ctx' => ['request_id' => bin2hex(random_bytes(4))]];
        $logger->debug('DEBUG sample log', $ctx);
        $logger->info('INFO sample log', $ctx);
        $logger->notice('NOTICE sample log', $ctx);
        $logger->warning('WARNING sample log', $ctx);
        $logger->error('ERROR sample log', $ctx);
        $logger->critical('CRITICAL sample log', $ctx);
        $logger->alert('ALERT sample log', $ctx);
        $logger->emergency('EMERGENCY sample log', $ctx);
        return new Response('Emitted logs for all levels. Check Sentry Logs.');
    }

    #[Route('/_debug/sentry/log/{level}', name: 'debug_sentry_log_level', requirements: ['level' => 'debug|info|notice|warning|error|critical|alert|emergency'])]
    public function logLevel(string $level, LoggerInterface $logger, KernelInterface $kernel): Response
    {
        if ($kernel->getEnvironment() !== 'dev') {
            throw $this->createNotFoundException();
        }
        $message = sprintf('Sentry dev test log level=%s', $level);
        $context = ['sample' => true, 'time' => time()];
        switch ($level) {
            case 'debug': $logger->debug($message, $context); break;
            case 'info': $logger->info($message, $context); break;
            case 'notice': $logger->notice($message, $context); break;
            case 'warning': $logger->warning($message, $context); break;
            case 'error': $logger->error($message, $context); break;
            case 'critical': $logger->critical($message, $context); break;
            case 'alert': $logger->alert($message, $context); break;
            case 'emergency': $logger->emergency($message, $context); break;
        }
        return new Response(sprintf('Logged a %s message.', strtoupper($level)));
    }
    #[Route('/_debug/sentry/message', name: 'debug_sentry_message')]
    public function message(KernelInterface $kernel): Response
    {
        if ($kernel->getEnvironment() !== 'dev') {
            throw $this->createNotFoundException();
        }
        \Sentry\captureMessage('Sentry dev test message');
        return new Response('Sentry message captured.');
    }
}
