<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findValidatedEvents();

        return $this->render('home/index.html.twig', [
            'events' => $events,
        ]);
    }

   #[Route('/events/filter', name: 'app_event_filter', methods: ['GET'])]
    public function filterEvents(Request $request, EventRepository $eventRepository): Response
    {
    $sort = $request->query->get('sort');

    $events = $eventRepository->findValidatedEventsSorted($sort);

    // Si la requête est AJAX (fetch), on renvoie uniquement le HTML partiel correspondant à la liste d'événements
    if ($request->isXmlHttpRequest()) {
        return $this->render('home/event_list_ajax.html.twig', [
            'events' => $events,
        ]);
    }

    // Sinon, on redirige vers la page d'accueil
    return $this->redirectToRoute('app_home');
    }

}
