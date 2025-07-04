<?php

namespace App\Service;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class EventPointsDistributor
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository
    ) {}

    public function distributePoints(int $points = 10): int
    {
        $events = $this->eventRepository->findFinishedEventsNotRewarded();
        $count = 0;

        foreach ($events as $event) {
            foreach ($event->getParticipants() as $participant) {
                $participant->addPoints($points);
                $this->em->persist($participant);
            }

            $event->setIsStarted(false); // désactiver le "en cours"
            $event->setIsFinished(true); // on marque comme fini
            $event->setPointsAwarded(true); // éviter le doublon
            $this->em->persist($event);
            $count++;
        }

        $this->em->flush();

        return $count;
    }
}
