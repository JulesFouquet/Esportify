<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    // Affiche la page d'accueil avec la liste des événements validés
    #[Route('/', name: 'app_home')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findValidatedEvents();

        return $this->render('home/index.html.twig', [
            'events' => $events,
        ]);
    }

    // Filtre et trie la liste des événements validés (via requête AJAX)
    #[Route('/events/filter', name: 'app_event_filter', methods: ['GET'])]
    public function filterEvents(Request $request, EventRepository $eventRepository): Response
    {
        $sort = $request->query->get('sort');

        $events = $eventRepository->findValidatedEventsSorted($sort);

        if ($request->isXmlHttpRequest()) {
            return $this->render('home/event_list_ajax.html.twig', [
                'events' => $events,
            ]);
        }

        return $this->redirectToRoute('app_home');
    }

    // Ajout : test simple pour vérifier que la session fonctionne
    #[Route('/test-session', name: 'app_test_session')]
    public function testSession(Request $request): Response
    {
        $session = $request->getSession();

        $session->set('foo', 'bar');
        $value = $session->get('foo');

        return new Response('Session test value: ' . $value);
    }
}
