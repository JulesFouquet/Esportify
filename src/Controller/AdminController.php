<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    // Page d'accueil du panneau admin affichant la liste des utilisateurs
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
        ]);
    }

    // Promotion d'un utilisateur à un rôle spécifique (ex: user, organisateur)
    #[Route('/promote/{id}/{role}', name: 'admin_promote')]
    public function promote(User $user, string $role, EntityManagerInterface $em): Response
    {
        $allowedRoles = ['ROLE_ORGANISATEUR', 'ROLE_ADMIN'];

        if (!in_array($role, $allowedRoles)) {
            $this->addFlash('danger', 'Rôle non autorisé.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $roles = $user->getRoles();

        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $user->setRoles(array_unique($roles));
            $em->flush();

            $this->addFlash('success', "L'utilisateur {$user->getEmail()} a été promu à {$role}.");
        } else {
            $this->addFlash('info', "L'utilisateur possède déjà ce rôle.");
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // Rétrogradation d'un utilisateur en retirant un rôle donné
    #[Route('/demote/{id}/{role}', name: 'admin_demote')]
    public function demote(User $user, string $role, EntityManagerInterface $em): Response
    {
        $protectedRoles = ['ROLE_USER'];
        $allRoles = $user->getRoles();

        if (in_array($role, $protectedRoles)) {
            $this->addFlash('danger', 'Ce rôle ne peut pas être retiré.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Retrait du rôle si présent
        if (in_array($role, $allRoles)) {
            $newRoles = array_filter($allRoles, fn($r) => $r !== $role);
            $user->setRoles(array_values($newRoles));
            $em->flush();

            $this->addFlash('warning', "L'utilisateur {$user->getEmail()} a été rétrogradé (rôle retiré : {$role}).");
        } else {
            $this->addFlash('info', "L'utilisateur ne possède pas ce rôle.");
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // Liste des événements en attente d'approbation par l'admin
    #[Route('/pending-events', name: 'admin_pending_events')]
    public function pendingEvents(EventRepository $eventRepository): Response
    {
        $pendingEvents = $eventRepository->findBy(['isAdminApproved' => false]);

        return $this->render('admin/pending_events.html.twig', [
            'pendingEvents' => $pendingEvents,
        ]);
    }

    // Validation d'un événement (passage à approuvé)
    #[Route('/approve-event/{id}', name: 'admin_approve_event')]
    public function approveEvent(Event $event, EntityManagerInterface $em): Response
    {
        $event->setIsAdminApproved(true);
        $em->flush();

        $this->addFlash('success', 'Événement validé avec succès.');

        return $this->redirectToRoute('admin_pending_events');
    }

    // Refus et suppression d'un événement
    #[Route('/reject-event/{id}', name: 'admin_reject_event')]
    public function rejectEvent(Event $event, EntityManagerInterface $em): Response
    {
        $em->remove($event);
        $em->flush();

        $this->addFlash('success', 'Événement refusé et supprimé.');

        return $this->redirectToRoute('admin_pending_events');
    }

    // Mise à jour d'un événement depuis une modale (formulaire POST)
    #[Route('/event/update/{id}', name: 'admin_event_update', methods: ['POST'])]
    public function updateEvent(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour sécuriser la requête
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('update_event' . $event->getId(), $submittedToken))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Récupération et validation simple des données postées
        $title = $request->request->get('title');
        $startDateTimeStr = $request->request->get('startDateTime');
        $endDateTimeStr = $request->request->get('endDateTime');
        $isApproved = $request->request->has('isApproved');
        $isStarted = $request->request->has('isStarted');
        $maxPlayers = $request->request->get('maxPlayers');

        if (!$title || !$startDateTimeStr || !$endDateTimeStr || !$maxPlayers) {
            $this->addFlash('danger', 'Tous les champs obligatoires doivent être remplis.');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $startDateTime = new \DateTime($startDateTimeStr);
            $endDateTime = new \DateTime($endDateTimeStr);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Dates invalides.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Mise à jour des propriétés de l'entité Event
        $event->setTitle($title);
        $event->setStartDateTime($startDateTime);
        $event->setEndDateTime($endDateTime);
        $event->setIsApproved($isApproved);
        $event->setIsStarted($isStarted);
        $event->setMaxPlayers((int)$maxPlayers);

        $em->flush();

        $this->addFlash('success', 'Événement mis à jour avec succès.');

        return $this->redirectToRoute('organisateur_events'); // Redirection vers la liste des events organisateur
    }

    // Suppression d'un événement
    #[Route('/event/delete/{id}', name: 'admin_event_delete', methods: ['GET', 'POST'])]
    public function deleteEvent(Event $event, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($event);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès.');

        return $this->redirectToRoute('organisateur_events'); // Redirection vers la liste des events organisateur
    }
}
