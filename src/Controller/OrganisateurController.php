<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrganisateurController extends AbstractController
{
    #[Route('/organisateur/events/propositions', name: 'organisateur_event_proposals')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function viewProposedEvents(EventRepository $eventRepository): Response
    {
        $proposedEvents = $eventRepository->findBy([
            'isApproved' => false,
        ]);

        return $this->render('organisateur/proposed_events.html.twig', [
            'proposedEvents' => $proposedEvents,
        ]);
    }

    #[Route('/organisateur/event/{id}/approve', name: 'organisateur_event_approve')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function approveEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $event->setIsApproved(true);
        $event->setIsAdminApproved(false);
        $event->setValidatedBy($this->getUser());

        $em->flush();

        $this->addFlash('success', 'Événement approuvé par un organisateur. En attente de validation par un administrateur.');
        return $this->redirectToRoute('organisateur_event_proposals');
    }

    #[Route('/organisateur/event/{id}/reject', name: 'organisateur_event_reject')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function rejectEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $em->remove($event);
        $em->flush();

        $this->addFlash('danger', 'Événement rejeté.');
        return $this->redirectToRoute('organisateur_event_proposals');
    }

    #[Route('/organisateur/events', name: 'organisateur_events')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function events(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy(
            ['isApproved' => true],
            ['startDateTime' => 'ASC']
        );

        return $this->render('organisateur/events.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/organisateur/event/{id}/start', name: 'organisateur_event_start', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function startEvent(int $id, Request $request, EntityManagerInterface $em, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        if (!$event->isApproved()) {
            throw $this->createAccessDeniedException('L\'événement n\'est pas approuvé.');
        }

        $now = new \DateTime();
        $startWindow = (clone $event->getStartDateTime())->modify('-30 minutes');
        $endTime = $event->getEndDateTime();

        if ($now < $startWindow || $now > $endTime) {
            throw $this->createAccessDeniedException('L\'événement ne peut être démarré qu\'entre 30 minutes avant son début et sa fin.');
        }

        if ($event->isStarted()) {
            $this->addFlash('warning', 'Cet événement est déjà démarré.');
            return $this->redirectToRoute('organisateur_events');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('start_event'.$event->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $event->setIsStarted(true);
        $em->flush();

        $this->addFlash('success', 'L\'événement a été démarré.');

        return $this->redirectToRoute('organisateur_events');
    }
}
