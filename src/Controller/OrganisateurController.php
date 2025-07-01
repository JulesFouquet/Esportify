<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Event;

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

    // Marque comme approuvé et en attente d’admin
    $event->setIsApproved(true);
    $event->setIsAdminApproved(false);

    // 🔥 Enregistre l’organisateur qui approuve
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
}
