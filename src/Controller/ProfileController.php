<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
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
    #[Route('/profil', name: 'app_profile')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(): JsonResponse|Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // On récupère les favoris pour affichage dans la vue
        $favoriteEvents = $user->getFavoriteEvents();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'favoriteEvents' => $favoriteEvents,
        ]);
    }

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
}
