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

class VoteController extends AbstractController
{
    #[Route('/vote/{uuid}', name: 'vote')]
    public function index(
        string $uuid,
        UsersRepository $usersRepository,
        ResponseType1Repository $responseType1Repository
    ): Response {
        $user = $usersRepository->findOneBy(['uuid' => $uuid]);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $factor = $user->getFactor();
        $event = $user->getEventId();
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

    #[Route('/vote/{uuid}/1/{proposalid}/{status}', name: 'submitvotetype1')]
    public function submitvotetype1(
        string $uuid,
        int $proposalid,
        string $status,
        UsersRepository $usersRepository,
        ProposalRepository $proposalRepository,
        ResponseType1Repository $responseType1Repository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $usersRepository->findOneBy(['uuid' => $uuid]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $proposal = $proposalRepository->findOneBy(['id' => $proposalid]);
        if (!$proposal) {
            throw $this->createNotFoundException('Proposal not found');
        }

        $factor = $user->getFactor();
        $event = $user->getEventId();

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
        }

        return $this->redirectToRoute('vote', ['uuid' => $uuid, 'factor' => $factor]);
    }
}
