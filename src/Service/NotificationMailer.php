<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NotificationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire(param: 'app.mail_from')] private readonly string $from,
    ) {}

    public function sendNewVote(string $to, string $uuid): void
    {
        $email = (new TemplatedEmail())
            ->from($this->from)
            ->to($to)
            ->subject('Admin : nouveau vote créé')
            ->htmlTemplate('emails/new_vote.html.twig')
            ->context(['uuid' => $uuid]);

        $this->mailer->send($email);
    }

    public function sendNewUser(string $to, string $uuid, $event): void
    {
        $email = (new TemplatedEmail())
            ->from($this->from)
            ->to($to)
            ->subject('Votre invitation au vote')
            ->htmlTemplate('emails/new_user.html.twig')
            ->context([
                'uuid' => $uuid,
                'event' => $event,
            ]);

        $this->mailer->send($email);
    }
}

