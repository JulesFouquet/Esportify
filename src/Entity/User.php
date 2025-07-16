<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Event;
use App\Entity\Participation;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private \DateTimeImmutable $dateInscription;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'organizer', targetEntity: Event::class)]
    private Collection $events;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $eventsJoined;

    #[ORM\OneToMany(mappedBy: 'player', targetEntity: Score::class)]
    private Collection $scores;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Message::class)]
    private Collection $messages;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\ManyToMany(targetEntity: Event::class)]
    #[ORM\JoinTable(name: 'user_favorite_events')]
    private Collection $favoriteEvents;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->eventsJoined = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->favoriteEvents = new ArrayCollection();
        $this->dateInscription = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }


    // -------------------- GETTERS & SETTERS --------------------

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getDateInscription(): \DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeImmutable $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setOrganizer($this);
        }
        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event) && $event->getOrganizer() === $this) {
            $event->setOrganizer(null);
        }
        return $this;
    }

    public function getEventsJoined(): Collection
    {
        return $this->eventsJoined;
    }

    public function addEventsJoined(Event $event): static
    {
        if (!$this->eventsJoined->contains($event)) {
            $this->eventsJoined->add($event);
            $event->addParticipant($this);
        }
        return $this;
    }

    public function removeEventsJoined(Event $event): static
    {
        if ($this->eventsJoined->removeElement($event)) {
            $event->removeParticipant($this);
        }
        return $this;
    }

    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(Score $score): static
    {
        if (!$this->scores->contains($score)) {
            $this->scores->add($score);
            $score->setPlayer($this);
        }
        return $this;
    }

    public function removeScore(Score $score): static
    {
        if ($this->scores->removeElement($score) && $score->getPlayer() === $this) {
            $score->setPlayer(null);
        }
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setAuthor($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message) && $message->getAuthor() === $this) {
            $message->setAuthor(null);
        }
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getFavoriteEvents(): Collection
    {
        return $this->favoriteEvents;
    }

    public function addFavoriteEvent(Event $event): static
    {
        if (!$this->favoriteEvents->contains($event)) {
            $this->favoriteEvents->add($event);
        }

        return $this;
    }

    public function removeFavoriteEvent(Event $event): static
    {
        $this->favoriteEvents->removeElement($event);
        return $this;
    }

    #[ORM\Column(type: 'integer')]
private int $points = 0;

public function getPoints(): int
{
    return $this->points;
}

public function addPoints(int $points): self
{
    $this->points += $points;
    return $this;
}
public function setPoints(int $points): self
{
    $this->points = $points;
    return $this;
}
}
