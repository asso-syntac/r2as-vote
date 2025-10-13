<?php

namespace App\Controller;

use App\Entity\Events;
use App\Entity\Proposal;
use App\Entity\Users;
use App\Entity\ResponseType1;
use App\Repository\EventsRepository;
use App\Repository\ProposalRepository;
use App\Repository\UsersRepository;
use App\Repository\ResponseType1Repository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Attribute\Route;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Doctrine\ORM\EntityManagerInterface;

class CreateVoteController extends AbstractController
{
    #[Route('/new-vote', name: 'create_vote')]
    public function index(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $uuid = uuid_create(UUID_TYPE_RANDOM);
        $event = new Events();
        $event->setUuid($uuid);
        $event->setState(true);
        $form = $this->createFormBuilder($event)
            ->add('name')
            ->add('description')
            ->add('mail', EmailType::class)
            ->add('save', SubmitType::class, ['label' => 'Valider'])
            ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->persist($event);
            $entityManager->flush();

            $mail = $form->get('mail')->getData();

            $email = (new TemplatedEmail())
                ->from('noemie.ployet@r2as.org')
                ->to($mail)
                ->subject('Admin : nouveau vote créé')
                ->htmlTemplate('emails/new_vote.html.twig')
                ->context([
                    'uuid' => $uuid,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Votre vote a été créé avec succès ! Un email de confirmation a été envoyé.');

            return $this->redirectToRoute('param_vote', ['uuid' => $uuid]);
        }


        return $this->render('create_vote/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/param-vote/{uuid}', name: 'param_vote')]
    public function paramvote(string $uuid, EventsRepository $eventsRepository): Response
    {
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('create_vote/param_vote.html.twig', [
            'uuid' => $uuid,
            'event' => $event,
        ]);
    }

    #[Route('/param-proposals/{uuid}', name: 'param_proposals')]
    public function paramproposals(
        Request $request,
        string $uuid,
        EventsRepository $eventsRepository,
        ProposalRepository $proposalRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $proposalss = $proposalRepository->findBy(['event_id' => $event]);
        $proposals = new Proposal();
        $proposals->setType("1");
        $proposals->setEventId($event);
        $form = $this->createFormBuilder($proposals)
            ->add('name')
            ->add('save', SubmitType::class, ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proposals = $form->getData();

            $entityManager->persist($proposals);
            $entityManager->flush();

            $this->addFlash('success', 'La proposition a été ajoutée avec succès.');

            return $this->redirectToRoute('param_vote', ['uuid' => $uuid]);
        }

        return $this->render('create_vote/param_proposals.html.twig', [
            'uuid' => $uuid,
            'event' => $event,
            'proposalss' => $proposalss,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/disable/{uuid}', name: 'disablevote', methods: ['POST'])]
    public function disablevote(
        Request $request,
        string $uuid,
        EventsRepository $eventsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('disable_vote' . $uuid, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        // Simple rate limit: 1 admin action per second per session (by event)
        $session = $request->getSession();
        $rateKey = 'rate.disable.' . $uuid;
        $now = time();
        $last = $session->get($rateKey, 0);
        if ($now - $last < 1) {
            $this->addFlash('error', 'Veuillez patienter une seconde avant de réessayer.');
            return $this->redirectToRoute('param_vote', ['uuid' => $uuid]);
        }
        $session->set($rateKey, $now);
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $event->setState(false);
        $entityManager->persist($event);
        $entityManager->flush();

        $this->addFlash('success', 'Le vote a été désactivé.');

        return $this->redirectToRoute('param_vote', ['uuid' => $uuid]);
    }

    #[Route('/enable/{uuid}', name: 'enablevote', methods: ['POST'])]
    public function enablevote(
        Request $request,
        string $uuid,
        EventsRepository $eventsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('enable_vote' . $uuid, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        // Simple rate limit: 1 admin action per second per session (by event)
        $session = $request->getSession();
        $rateKey = 'rate.enable.' . $uuid;
        $now = time();
        $last = $session->get($rateKey, 0);
        if ($now - $last < 1) {
            $this->addFlash('error', 'Veuillez patienter une seconde avant de réessayer.');
            return $this->redirectToRoute('param_vote', ['uuid' => $uuid]);
        }
        $session->set($rateKey, $now);
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $event->setState(true);
        $entityManager->persist($event);
        $entityManager->flush();

        $this->addFlash('success', 'Le vote a été activé.');

        return $this->redirectToRoute('param_vote', ['uuid' => $uuid]);
    }

    #[Route('/results/{uuid}', name: 'results')]
    public function results(
        string $uuid,
        EventsRepository $eventsRepository,
        ProposalRepository $proposalRepository,
        ResponseType1Repository $responseType1Repository
    ): Response {
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $proposals = $proposalRepository->findBy(['event_id' => $event]);
        $responsesType1 = $responseType1Repository->findBy(['event_id' => $event]);

        $response = [];
        foreach ($responsesType1 as $r){
            $userUuid = null; $userName = null; $userMail = null; $userId = null;
            try {
                $user = $r->getUserId();
                if ($user) {
                    $userId = $user->getId();
                    $userUuid = $user->getUuid();
                    $userName = $user->getName();
                    $userMail = $user->getMail();
                }
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                // utilisateur supprimé: on laisse les champs à null
            }
            $response[] = [
                'proposal' => $r->getProposalId()->getId(),
                'positive' => $r->getpositive(),
                'negative' => $r->getnegative(),
                'abstention' => $r->getabstention(),
                'user_id' => $userId,
                'user_uuid' => $userUuid,
                'user_name' => $userName,
                'user_mail' => $userMail,
            ];
        }

        return $this->render('create_vote/results.html.twig', [
            'uuid' => $uuid,
            'event' => $event,
            'proposals' => $proposals,
            'responsesType1' => $response,
        ]);
    }

    #[Route('/delete-user/{uuid}/{userid}', name: 'deleteuser', methods: ['POST'])]
    public function deleteuser(
        Request $request,
        string $uuid,
        int $userid,
        EventsRepository $eventsRepository,
        UsersRepository $usersRepository,
        \App\Repository\ResponseType1Repository $responseType1Repository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_user' . $userid, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        // Simple rate limit: 1 delete per second per session (by user ID)
        $session = $request->getSession();
        $rateKey = 'rate.delete_user.' . $userid;
        $now = time();
        $last = $session->get($rateKey, 0);
        if ($now - $last < 1) {
            $this->addFlash('error', 'Veuillez patienter une seconde avant de réessayer.');
            return $this->redirectToRoute('list_users', ['uuid' => $uuid]);
        }
        $session->set($rateKey, $now);
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $user = $usersRepository->findOneBy(['id' => $userid]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $userevent = $user->getEventId()->getId();
        $eventid = $event->getId();
        if ($userevent == $eventid){
            // Supprimer d'abord les votes associés à cet utilisateur
            $userVotes = $responseType1Repository->findBy(['user_id' => $user]);
            foreach ($userVotes as $v) {
                $entityManager->remove($v);
            }
            // Puis supprimer l'utilisateur
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Le votant et ses votes associés ont été supprimés.');
        }

        return $this->redirectToRoute('param_users', ['uuid' => $uuid]);
    }

    #[Route('/delete-proposal/{uuid}/{proposalid}', name: 'deleteproposal', methods: ['POST'])]
    public function deleteproposal(
        Request $request,
        string $uuid,
        int $proposalid,
        EventsRepository $eventsRepository,
        ProposalRepository $proposalRepository,
        ResponseType1Repository $responseType1Repository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_proposal' . $proposalid, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        // Simple rate limit: 1 delete per second per session (by proposal ID)
        $session = $request->getSession();
        $rateKey = 'rate.delete_proposal.' . $proposalid;
        $now = time();
        $last = $session->get($rateKey, 0);
        if ($now - $last < 1) {
            $this->addFlash('error', 'Veuillez patienter une seconde avant de réessayer.');
            return $this->redirectToRoute('param_proposals', ['uuid' => $uuid]);
        }
        $session->set($rateKey, $now);
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $proposal = $proposalRepository->findOneBy(['id' => $proposalid]);
        if (!$proposal) {
            throw $this->createNotFoundException('Proposal not found');
        }

        $proposalevent = $proposal->getEventId()->getId();
        $eventid = $event->getId();
        if ($proposalevent == $eventid){
            // Supprimer d'abord les votes rattachés à cette proposition
            $proposalVotes = $responseType1Repository->findBy(['proposal_id' => $proposal]);
            foreach ($proposalVotes as $v) {
                $entityManager->remove($v);
            }
            // Puis supprimer la proposition
            $entityManager->remove($proposal);
            $entityManager->flush();
            $this->addFlash('success', 'La proposition et ses votes associés ont été supprimés.');
        }

        return $this->redirectToRoute('param_proposals', ['uuid' => $uuid]);
    }

    #[Route('/list-users/{uuid}', name: 'list_users')]
    public function listusers(
        string $uuid,
        EventsRepository $eventsRepository,
        UsersRepository $usersRepository
    ): Response {
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $users = $usersRepository->findBy(['event_id' => $event]);

        return $this->render('create_vote/list_users.html.twig', [
            'uuid' => $uuid,
            'event' => $event,
            'users' => $users,
        ]);
    }

    #[Route('/param-users/{uuid}', name: 'param_users')]
    public function paramusers(
        Request $request,
        string $uuid,
        MailerInterface $mailer,
        EventsRepository $eventsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $users = new Users();
        $uuidd = uuid_create(UUID_TYPE_RANDOM);
        $users->setUuid($uuidd);
        $users->setEventId($event);
        $form = $this->createFormBuilder($users)
            ->add('mail', EmailType::class)
            ->add('name')
            ->add('factor', ChoiceType::class, [
                'choices' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $users = $form->getData();

            $entityManager->persist($users);
            $entityManager->flush();

            $mail = $form->get('mail')->getData();

            $email = (new TemplatedEmail())
                ->from('noemie.ployet@r2as.org')
                ->to($mail)
                ->subject('Votre invitation au vote')
                ->htmlTemplate('emails/new_user.html.twig')
                ->context([
                    'uuid' => $uuidd,
                    'event' => $event,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Le votant a été ajouté et un email lui a été envoyé.');

            return $this->redirectToRoute('param_users', ['uuid' => $uuid]);
        }

        return $this->render('create_vote/param_users.html.twig', [
            'uuid' => $uuid,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/param-users-batch/{uuid}', name: 'param_users_batch')]
    public function paramusersbatch(
        Request $request,
        string $uuid,
        MailerInterface $mailer,
        EventsRepository $eventsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $eventsRepository->findOneBy(['uuid' => $uuid]);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $form = $this->createFormBuilder()
            ->add('csv', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $import = $form->get('csv')->getData();

            $reader = Reader::createFromString($import);
            $records = $reader->getRecords(['name', 'email', 'factor']);

            foreach ($records as $row => $record){
                $uuidd = uuid_create(UUID_TYPE_RANDOM);
                $users = new Users();
                $users
                    ->setUuid($uuidd)
                    ->setEventId($event)
                    ->setMail($record['email'])
                    ->setName($record['name'])
                    ->setFactor($record['factor'])
                ;
                $entityManager->persist($users);
                $entityManager->flush();

                $email = (new TemplatedEmail())
                    ->from('noemie.ployet@r2as.org')
                    ->to($record['email'])
                    ->subject('Votre invitation au vote')
                    ->htmlTemplate('emails/new_user.html.twig')
                    ->context([
                        'uuid' => $uuidd,
                        'event' => $event,
                    ]);

                $mailer->send($email);
            }

            $this->addFlash('success', 'Les votants ont été importés avec succès et les emails ont été envoyés.');

            return $this->redirectToRoute('param_users_batch', ['uuid' => $uuid]);
        }

        return $this->render('create_vote/param_users_batch.html.twig', [
            'uuid' => $uuid,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
