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

    public function listValidatedEvents(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findValidatedEvents();

        return $this->render('event/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event/new', name: 'app_event_create')]
#[IsGranted('ROLE_USER')]
public function create(Request $request, EntityManagerInterface $em): Response
{
    $event = new Event();
    $form = $this->createForm(EventType::class, $event);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $event->setIsApproved(false);
        $event->setIsAdminApproved(false);
        $event->setIsStarted(false);
        $event->setOrganizer($this->getUser());

        // 🖼️ Logique pour associer une image au jeu choisi
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


    #[Route('/event/{id}', name: 'app_event_show')]
    public function show(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        if (!$event->isApproved() || !$event->isAdminApproved()) {
            throw $this->createAccessDeniedException("Cet événement n'est pas encore publié.");
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setAuthor($this->getUser());
            $message->setEvent($event);
            $message->setCreatedAt(new \DateTimeImmutable());

            $em->persist($message);
            $em->flush();

            $this->addFlash('success', 'Message envoyé !');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $messages = $event->getMessages();

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'messageForm' => $form->createView(),
            'messages' => $messages,
        ]);
    }

    #[Route('/event/{id}/register', name: 'event_register')]
#[IsGranted('ROLE_USER')]
public function register(Event $event, EntityManagerInterface $em): RedirectResponse
{
    $user = $this->getUser();

    // ❌ Vérification si l'événement est validé
    if (!$event->isApproved() || !$event->isAdminApproved()) {
        $this->addFlash('error', 'Cet événement n’est pas encore validé.');
        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    // ❌ Vérification si l'événement est terminé
    if ($event->isFinished()) {
        $this->addFlash('error', 'L\'événement est terminé, vous ne pouvez plus vous inscrire.');
        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    // ❌ Vérification si l'utilisateur est banni
    foreach ($event->getEventBans() as $ban) {
        if ($ban->getUser() === $user) {
            $this->addFlash('error', '⚠️ Vous avez été banni de cet événement.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }
    }

    // ❌ Vérifie si l'événement est plein
    if ($event->getParticipants()->count() >= $event->getMaxPlayers()) {
        $this->addFlash('error', 'Le nombre maximal de participants est atteint.');
        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    // ✅ Inscription si non inscrit
    if (!$event->getParticipants()->contains($user)) {
        $event->addParticipant($user);
        $em->flush();
        $this->addFlash('success', '✅ Vous êtes inscrit au tournoi !');
    } else {
        $this->addFlash('info', 'ℹ️ Vous êtes déjà inscrit à cet événement.');
    }

    return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
}


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
            $event->setIsApproved(false);
            $event->setIsAdminApproved(false);

            $em->flush();

            $this->addFlash('success', 'Événement modifié avec succès. Il est à nouveau en attente de validation.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}/delete', name: 'user_event_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }

        if ($event->isStarted()) {
            $this->addFlash('error', 'Vous ne pouvez plus supprimer un événement déjà démarré.');
            return $this->redirectToRoute('app_profile');
        }

        $em->remove($event);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès.');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/event/{id}/finish', name: 'event_finish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function finishEvent(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        $submittedToken = $request->request->get('_token');

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('finish_event' . $event->getId(), $submittedToken))) {
            return new JsonResponse(['success' => false, 'message' => 'Jeton CSRF invalide.'], 400);
        }

        if ($event->isFinished()) {
            return new JsonResponse(['success' => false, 'message' => 'Événement déjà terminé.'], 400);
        }

        $event->setIsFinished(true);
        $this->distributePoints($event, $em);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Événement terminé.']);
    }

    private function distributePoints(Event $event, EntityManagerInterface $em): void
    {
        foreach ($event->getParticipants() as $user) {
            $currentPoints = $user->getPoints() ?? 0;
            $user->setPoints($currentPoints + 10);
            $em->persist($user);
        }
    }

    #[Route('/event/{eventId}/refuse/{userId}', name: 'app_event_refuse_participant', methods: ['POST'])]
    public function refuseParticipant(
        int $eventId,
        int $userId,
        EventRepository $eventRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $event = $eventRepo->find($eventId);
        $user = $userRepo->find($userId);

        if (!$event || !$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur ou événement introuvable'], 404);
        }

        if (!$this->isCsrfTokenValid('refuse_' . $eventId . '_' . $userId, $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $event->removeParticipant($user);

        $ban = new EventBan();
        $ban->setEvent($event);
        $ban->setUser($user);
        $em->persist($ban);

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Utilisateur exclu et ajouté à la liste noire.']);
    }
}
