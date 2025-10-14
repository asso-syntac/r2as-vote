<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Events;
use App\Entity\Proposal;
use App\Entity\Users;
use App\Entity\ResponseType1;
use App\Repository\UsersRepository;
use App\Repository\ProposalRepository;
use App\Repository\ResponseType1Repository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Symfony\Component\Mercure\HubInterface as MercureHubInterface;
use Symfony\Component\Mercure\Update;

class VoteController extends AbstractController
{
    #[Route('/vote/{uuid}', name: 'vote')]
    public function index(
        string $uuid,
        UsersRepository $usersRepository,
        ResponseType1Repository $responseType1Repository,
        HubInterface $sentryHub
    ): Response {
        $user = $usersRepository->findOneBy(['uuid' => $uuid]);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $factor = $user->getFactor();
        $event = $user->getEventId();
        // Identify voter in Sentry context
        $sentryHub->configureScope(function (Scope $scope) use ($user) {
            $scope->setUser([
                'id' => $user->getUuid(),
                'email' => $user->getMail(),
                'username' => $user->getName(),
            ]);
            $scope->setTag('role', 'voter');
        });
        // Tag event UUID for correlation
        $sentryHub->configureScope(function (Scope $scope) use ($event) {
            $scope->setTag('event_uuid', $event->getUuid());
        });
        $proposals = $event->getProposals();
        $responsesType1 = $responseType1Repository->findBy(['user_id' => $user]);

        $eventstate = $event->getState();
        if ($eventstate == false) {
            return $this->render('vote/deactivated_vote.html.twig', [
                'user' => $user,
                'uuid' => $uuid,
                'event' => $event,
            ]);
        }
        $exist[] = '0';
        foreach ($responsesType1 as $response){
             $exist[] = ($response->getProposalId()->getId());
        }

        return $this->render('vote/index.html.twig', [
            'user' => $user,
            'uuid' => $uuid,
            'event' => $event,
            'factor' => $factor,
            'exist' => $exist,
            'proposals' => $proposals,
            'responsesType1' => $responsesType1,
        ]);
    }

    #[Route('/vote/{uuid}/1/{proposalid}/{status}', name: 'submitvotetype1', methods: ['POST'])]
    public function submitvotetype1(
        Request $request,
        string $uuid,
        int $proposalid,
        string $status,
        UsersRepository $usersRepository,
        ProposalRepository $proposalRepository,
        ResponseType1Repository $responseType1Repository,
        EntityManagerInterface $entityManager,
        HubInterface $sentryHub,
        MercureHubInterface $mercureHub
    ): Response {
        $allowed = ['positive', 'negative', 'abstention'];
        if (!in_array($status, $allowed, true)) {
            throw $this->createNotFoundException('Invalid status');
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('vote_type1' . $uuid . '-' . $proposalid . '-' . $status, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Simple rate limit: 1 vote action per second per user session
        $session = $request->getSession();
        $rateKey = 'rate.vote.' . $uuid;
        $now = time();
        $last = $session->get($rateKey, 0);
        if ($now - $last < 1) {
            $this->addFlash('error', 'Veuillez patienter une seconde avant de voter de nouveau.');
            return $this->redirectToRoute('vote', ['uuid' => $uuid]);
        }
        $session->set($rateKey, $now);
        $user = $usersRepository->findOneBy(['uuid' => $uuid]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        // Identify voter in Sentry context for this action
        $sentryHub->configureScope(function (Scope $scope) use ($user) {
            $scope->setUser([
                'id' => $user->getUuid(),
                'email' => $user->getMail(),
                'username' => $user->getName(),
            ]);
            $scope->setTag('role', 'voter');
        });

        $proposal = $proposalRepository->findOneBy(['id' => $proposalid]);
        if (!$proposal) {
            throw $this->createNotFoundException('Proposal not found');
        }

        $factor = $user->getFactor();
        $event = $user->getEventId();
        if ($event->getState() === false) {
            $this->addFlash('error', 'Le vote est désactivé.');
            return $this->redirectToRoute('vote', ['uuid' => $uuid]);
        }
        if ($proposal->getEventId()->getId() !== $event->getId()) {
            throw $this->createNotFoundException('Proposal not in user event');
        }
        // Tag event and proposal for filtering
        $sentryHub->configureScope(function (Scope $scope) use ($event, $proposal, $status) {
            $scope->setTag('event_uuid', $event->getUuid());
            $scope->setTag('proposal_id', (string) $proposal->getId());
            $scope->setTag('vote_status', $status);
        });

        $vote = new ResponseType1();
        $vote->setEventId($event);
        $vote->setUserId($user);
        $vote->setProposalId($proposal);

        if ($status == 'positive') {
            $vote->setPositive($factor);
        }

        if ($status == 'negative') {
            $vote->setNegative($factor);
        }

        if ($status == 'abstention') {
            $vote->setAbstention($factor);
        }

        $vote_exist = $responseType1Repository->findBy(['user_id' => $user, 'proposal_id' => $proposal]);
        if ($vote_exist) {
            $this->addFlash('error', 'Vous avez déjà voté pour cette proposition.');
            return $this->redirectToRoute('vote', ['uuid' => $uuid]);
        } else {
            $entityManager->persist($vote);
            $entityManager->flush();
            $this->addFlash('success', 'Votre vote a été enregistré avec succès.');
            // Publish Mercure update (JWT handled by MercureBundle)
            $topic = sprintf('https://r2as.example/topics/event/%s', $event->getUuid());
            $mercureHub->publish(new Update($topic, json_encode([
                'type' => 'vote.cast',
                'event_uuid' => $event->getUuid(),
                'proposal_id' => $proposal->getId(),
                'status' => $status,
                'factor' => $factor,
                'timestamp' => time(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        }

        return $this->redirectToRoute('vote', ['uuid' => $uuid, 'factor' => $factor]);
    }
}
