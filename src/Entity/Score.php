<?php

namespace App\Entity;

use App\Repository\ScoreRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Event;

#[ORM\Entity(repositoryClass: ScoreRepository::class)]
class Score
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scores')]
    private ?User $player = null;

    #[ORM\ManyToOne(inversedBy: 'scores')]
    private ?Event $event = null;

    #[ORM\Column]
    private ?int $points = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    /**
     * Crée un objet Score avec points calculés selon le rang
     *
     * @param User $player
     * @param Event $event
     * @param int $rank Rang du joueur (1 = premier, 2 = deuxième, etc.)
     * @return self
     */
    public static function createScoreForPlayer(User $player, Event $event, int $rank): self
    {
        $score = new self();
        $score->setPlayer($player);
        $score->setEvent($event);

        $basePoints = 5;
        $bonusPoints = 0;

        switch ($rank) {
            case 1:
                $bonusPoints = 15;
                break;
            case 2:
                $bonusPoints = 10;
                break;
            case 3:
                $bonusPoints = 5;
                break;
            default:
                $bonusPoints = 0;
        }

        $score->setPoints($basePoints + $bonusPoints);

        return $score;
    }
}
