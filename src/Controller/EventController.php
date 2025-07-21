<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventBan;
use App\Entity\Message;
use App\Form\EventType;
use App\Form\MessageType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EventController extends AbstractController
{
    // Liste publique des événements validés et non démarrés ou démarrés
    #[Route('/events', name: 'app_event_list')]
    public function listEvents(EventRepository $eventRepository): Response
    {
        $now = new \DateTime();

        $events = $eventRepository->createQueryBuilder('e')
            ->where('e.isApproved = true')
            ->andWhere('e.isAdminApproved = true')
            ->andWhere('e.startDateTime >= :now OR e.isStarted = true')
            ->andWhere('e.validatedBy IS NOT NULL')
            ->setParameter('now', $now)
            ->orderBy('e.startDateTime', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    // Création d'un événement proposé par un utilisateur
    #[Route('/event/new', name: 'app_event_create')]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Initialisation de l'état d'approbation
            $event->setIsApproved(false);
            $event->setIsAdminApproved(false);
            $event->setIsStarted(false);
            $event->setOrganizer($this->getUser());

            // Association image selon jeu sélectionné
            $game = $event->getGame();
            $imageMap = [
                'CS2' => 'cs2.jpg',
                'Dota2' => 'dota2.jpg',
                'League of Legends' => 'lol.jpg',
                'Rocket League' => 'rocketleague.jpg',
                'Super Smash Bros.' => 'smashbros.jpg',
                'TFT' => 'tft.jpg',
                'Valorant' => 'valorant.jpg',
            ];

            if (isset($imageMap[$game])) {
                $event->setImage($imageMap[$game]);
            }

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Votre événement a été proposé avec succès. En attente de validation.');

            return $this->redirectToRoute('app_event_list');
        }

        return $this->render('event/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Affichage d'un événement (sans chat)
    #[Route('/event/{id}', name: 'app_event_show')]
    public function show(Event $event): Response
    {
        if (!$event->isApproved() || !$event->isAdminApproved()) {
            throw $this->createAccessDeniedException("Cet événement n'est pas encore publié.");
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    // Page dédiée au chat de l'événement
    #[Route('/event/{id}/chat', name: 'chat_event')]
    #[IsGranted('ROLE_USER')]
    public function chat(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        if (!$event->isApproved() || !$event->isAdminApproved()) {
            throw $this->createAccessDeniedException("Cet événement n'est pas encore publié.");
        }

        $user = $this->getUser();

        // Vérifie que l'utilisateur est inscrit au tournoi
        if (!$event->getParticipants()->contains($user)) {
            $this->addFlash('error', 'Vous devez être inscrit pour accéder au chat.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setAuthor($user);
            $message->setEvent($event);
            $message->setCreatedAt(new \DateTimeImmutable());

            $em->persist($message);
            $em->flush();

            $this->addFlash('success', 'Message envoyé !');

            return $this->redirectToRoute('chat_event', ['id' => $event->getId()]);
        }

        $messages = $event->getMessages();

        return $this->render('event/chat.html.twig', [
            'event' => $event,
            'messageForm' => $form->createView(),
            'messages' => $messages,
        ]);
    }

    // Inscription à un événement (User)
    #[Route('/event/{id}/register', name: 'event_register')]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if (!$event->isApproved() || !$event->isAdminApproved()) {
            $this->addFlash('error', 'Cet événement n’est pas encore validé.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->isFinished()) {
            $this->addFlash('error', 'L\'événement est terminé, vous ne pouvez plus vous inscrire.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        // Vérification bannissement
        foreach ($event->getEventBans() as $ban) {
            if ($ban->getUser() === $user) {
                $this->addFlash('error', '⚠️ Vous avez été banni de cet événement.');
                return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
            }
        }

        if ($event->getParticipants()->count() >= $event->getMaxPlayers()) {
            $this->addFlash('error', 'Le nombre maximal de participants est atteint.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if (!$event->getParticipants()->contains($user)) {
            $event->addParticipant($user);
            $em->flush();
            $this->addFlash('success', '✅ Vous êtes inscrit au tournoi !');
        } else {
            $this->addFlash('info', 'ℹ️ Vous êtes déjà inscrit à cet événement.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    // Désinscription d'un événement
    #[Route('/event/{id}/unregister', name: 'event_unregister')]
    #[IsGranted('ROLE_USER')]
    public function unregister(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if ($event->getParticipants()->contains($user)) {
            $event->removeParticipant($user);
            $em->flush();
            $this->addFlash('success', 'Vous êtes désinscrit du tournoi.');
        } else {
            $this->addFlash('info', 'Vous n’êtes pas inscrit à ce tournoi.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    // Modification d'un événement par son créateur tant que non démarré
    #[Route('/event/{id}/edit', name: 'user_event_update')]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet événement.');
        }

        if ($event->isStarted()) {
            $this->addFlash('error', 'Vous ne pouvez plus modifier un événement déjà démarré.');
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Repassage en attente validation à chaque modif
            $event->setIsApproved(false);
            $event->setIsAdminApproved(false);

            $em->flush();

            $this->addFlash('success', 'Événement modifié avec succès. Il est à nouveau en attente de validation.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('event/update.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    // Suppression d'un événement par son créateur
    #[Route('/event/{id}/delete', name: 'user_event_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }

        $em->remove($event);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé.');

        return $this->redirectToRoute('app_profile');
    }

    // Terminer un événement (organisateur)
    #[Route('/event/{id}/finish', name: 'organisateur_event_finish')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function finishEvent(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        if ($event->isStarted() && !$event->isFinished()) {
            $event->setIsFinished(true);
            $em->flush();
            $this->addFlash('success', 'Événement terminé avec succès.');
        } else {
            $this->addFlash('error', 'Impossible de terminer cet événement.');
        }

        return $this->redirectToRoute('organisateur_events');
    }

    // Refuser la participation d'un joueur (organisateur)
    #[Route('/event/{id}/refuse/{userId}', name: 'organisateur_event_refuse')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function refuseParticipant(Event $event, int $userId, UserRepository $userRepository, EntityManagerInterface $em): RedirectResponse
    {
        $userToBan = $userRepository->find($userId);
        if (!$userToBan) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('organisateur_events');
        }

        // Ajouter un bannissement à l'événement
        $eventBan = new EventBan();
        $eventBan->setUser($userToBan);
        $eventBan->setEvent($event);
        $em->persist($eventBan);

        // Retirer participant de l'événement
        if ($event->getParticipants()->contains($userToBan)) {
            $event->removeParticipant($userToBan);
        }

        $em->flush();

        $this->addFlash('success', 'Participation refusée et utilisateur banni.');

        return $this->redirectToRoute('organisateur_events');
    }
}
