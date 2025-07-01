<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Message;
use App\Form\EventType;
use App\Form\MessageType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            ->andWhere('e.validatedBy IS NOT NULL') // <-- Ajout ici
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
            $event->setIsApproved(false); // Organisateur doit valider
            $event->setIsAdminApproved(false); // Admin doit valider
            $event->setIsStarted(false);
            $event->setOrganizer($this->getUser());

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
        // Rediriger si l’événement n’est pas validé par les deux
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

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'messageForm' => $form->createView(),
        ]);
    }

    #[Route('/event/{id}/register', name: 'event_register')]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if (!$event->isApproved() || !$event->isAdminApproved()) {
            $this->addFlash('error', 'Cet événement n’est pas encore validé.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->getParticipants()->count() >= $event->getMaxPlayers()) {
            $this->addFlash('error', 'Le nombre maximal de participants est atteint.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if (!$event->getParticipants()->contains($user)) {
            $event->addParticipant($user);
            $em->flush();
            $this->addFlash('success', 'Vous êtes inscrit au tournoi !');
        } else {
            $this->addFlash('info', 'Vous êtes déjà inscrit.');
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
}
