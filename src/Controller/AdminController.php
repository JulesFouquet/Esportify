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

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
        ]);
    }

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

    #[Route('/demote/{id}/{role}', name: 'admin_demote')]
    public function demote(User $user, string $role, EntityManagerInterface $em): Response
    {
        $protectedRoles = ['ROLE_USER']; // Ne jamais retirer ROLE_USER
        $allRoles = $user->getRoles();

        if (in_array($role, $protectedRoles)) {
            $this->addFlash('danger', 'Ce rôle ne peut pas être retiré.');
            return $this->redirectToRoute('admin_dashboard');
        }

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

    #[Route('/pending-events', name: 'admin_pending_events')]
public function pendingEvents(EventRepository $eventRepository): Response
{
    // On récupère les événements non approuvés par l'admin
    $pendingEvents = $eventRepository->findBy(['isAdminApproved' => false]);

    return $this->render('admin/pending_events.html.twig', [
        'pendingEvents' => $pendingEvents,
    ]);
}

#[Route('/approve-event/{id}', name: 'admin_approve_event')]
public function approveEvent(Event $event, EntityManagerInterface $em): Response
{
    $event->setIsAdminApproved(true);
    $em->flush();

    $this->addFlash('success', 'Événement validé avec succès.');

    return $this->redirectToRoute('admin_pending_events');
}

#[Route('/reject-event/{id}', name: 'admin_reject_event')]
public function rejectEvent(Event $event, EntityManagerInterface $em): Response
{
    // Ici tu peux soit supprimer l'événement, soit mettre un flag "refusé"
    $em->remove($event);
    $em->flush();

    $this->addFlash('success', 'Événement refusé et supprimé.');

    return $this->redirectToRoute('admin_pending_events');
}
}
