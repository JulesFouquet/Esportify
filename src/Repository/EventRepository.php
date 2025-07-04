<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Retourne les événements validés par l'organisateur ET l'admin.
     *
     * @return Event[]
     */
    public function findValidatedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isApproved = true')
            ->andWhere('e.isAdminApproved = true')
            ->andWhere('e.validatedBy IS NOT NULL')
            ->orderBy('e.startDateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements validés, triés dynamiquement selon un critère.
     *
     * @param string|null $sortBy
     * @return Event[]
     */
    public function findValidatedEventsSorted(?string $sortBy): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.organizer', 'o')
            ->where('e.isApproved = true')
            ->andWhere('e.isAdminApproved = true')
            ->andWhere('e.validatedBy IS NOT NULL');

        switch ($sortBy) {
            case 'players':
                $qb->orderBy('e.maxPlayers', 'DESC');
                break;
            case 'organizer':
                $qb->orderBy('o.pseudo', 'ASC');
                break;
            case 'date':
            default:
                $qb->orderBy('e.startDateTime', 'ASC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function findFinishedEventsNotRewarded(): array
{
    $now = new \DateTime();

    return $this->createQueryBuilder('e')
        ->where('e.endDateTime <= :now')
        ->andWhere('e.isStarted = true')
        ->andWhere('e.pointsAwarded = false')
        ->setParameter('now', $now)
        ->getQuery()
        ->getResult();
}

}
