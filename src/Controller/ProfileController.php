<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ProfileController extends AbstractController
{
    // Affiche la page de profil avec les favoris, événements créés, inscrits et scores de l'utilisateur connecté
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(EventRepository $eventRepository, ScoreRepository $scoreRepository): JsonResponse|Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Favoris
        $favoriteEvents = $user->getFavoriteEvents();

        // Événements créés par l'utilisateur
        $createdEvents = $eventRepository->findBy(['organizer' => $user]);

        $approvedEvents = [];
        $pendingEvents = [];
        $modifiableEvents = [];

        $now = new \DateTime();

        foreach ($createdEvents as $event) {
            if ($event->isApproved()) {
                $approvedEvents[] = $event;

                if ($event->getStartDateTime() > $now && $event->getOrganizer() === $user) {
                    $modifiableEvents[] = $event;
                }
            } else {
                $pendingEvents[] = $event;

                if ($event->getStartDateTime() > $now && $event->getOrganizer() === $user) {
                    $modifiableEvents[] = $event;
                }
            }
        }

        $modifiableEventIds = array_map(fn($e) => $e->getId(), $modifiableEvents);

        // Scores de l'utilisateur
        $scores = $scoreRepository->findBy(['player' => $user]);

        // Événements où l'utilisateur est inscrit
        $registeredEvents = $eventRepository->createQueryBuilder('e')
            ->innerJoin('e.participants', 'p')
            ->where('p.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'favoriteEvents' => $favoriteEvents,
            'createdEvents' => $approvedEvents,
            'pendingEvents' => $pendingEvents,
            'modifiableEventIds' => $modifiableEventIds,
            'scores' => $scores,
            'registeredEvents' => $registeredEvents,
        ]);
    }

    // Toggle l'ajout ou la suppression d'un événement dans les favoris de l'utilisateur via requête AJAX
    #[Route('/favoris/toggle/{id}', name: 'app_profile_favorite_toggle', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function toggleFavorite(Event $event, EntityManagerInterface $em, Request $request, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], 403);
        }

        $submittedToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('toggle_favorite', $submittedToken))) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 400);
        }

        if ($user->getFavoriteEvents()->contains($event)) {
            $user->removeFavoriteEvent($event);
            $status = 'removed';
        } else {
            $user->addFavoriteEvent($event);
            $status = 'added';
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'status' => $status,
            'eventId' => $event->getId(),
        ]);
    }

    // Permet à l'utilisateur de modifier un événement qu'il a créé, uniquement avant son début, avec vérification CSRF
    #[Route('/profil/event/update/{id}', name: 'user_event_update', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateEvent(Request $request, Event $event, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet événement.');
        }

        if ($event->getStartDateTime() <= new \DateTime()) {
            $this->addFlash('error', 'Cet événement a déjà commencé, vous ne pouvez plus le modifier.');
            return $this->redirectToRoute('app_profile');
        }

        $submittedToken = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('user_update_event' . $event->getId(), $submittedToken))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $title = $request->request->get('title');
        $startDateTime = $request->request->get('startDateTime');
        $endDateTime = $request->request->get('endDateTime');
        $maxPlayers = $request->request->get('maxPlayers');

        $event->setTitle($title);
        $event->setStartDateTime(new \DateTime($startDateTime));
        $event->setEndDateTime(new \DateTime($endDateTime));
        $event->setMaxPlayers((int)$maxPlayers);

        $event->setIsApproved(false);

        $em->persist($event);
        $em->flush();

        $this->addFlash('success', "L'événement a bien été modifié et repasse en attente de validation.");

        return $this->redirectToRoute('app_profile');
    }
}
