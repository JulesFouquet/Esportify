<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTime $startDateTime = null;

    #[ORM\Column]
    private ?\DateTime $endDateTime = null;

    #[ORM\Column]
    private ?int $maxPlayers = null;

    #[ORM\Column]
    private ?bool $isApproved = null;

    #[ORM\Column]
    private ?bool $isStarted = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    /**
     * Participants à l'événement.
     * 
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'eventsJoined')]
    #[ORM\JoinTable(name: 'event_participants')]
    private Collection $participants;

    /**
     * @var Collection<int, Score>
     */
    #[ORM\OneToMany(targetEntity: Score::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $scores;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $messages;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $images;

    #[ORM\Column(type: 'boolean')]
    private bool $isAdminApproved = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validatedBy = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStartDateTime(): ?\DateTime
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(\DateTime $startDateTime): static
    {
        $this->startDateTime = $startDateTime;
        return $this;
    }

    public function getEndDateTime(): ?\DateTime
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(\DateTime $endDateTime): static
    {
        $this->endDateTime = $endDateTime;
        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;
        return $this;
    }

    public function isApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;
        return $this;
    }

    public function isStarted(): ?bool
    {
        return $this->isStarted;
    }

    public function setIsStarted(bool $isStarted): static
    {
        $this->isStarted = $isStarted;
        return $this;
    }

    public function getParticipantsCount(): int
    {
        return $this->participants->count();
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }

        return $this;
    }

    public function removeParticipant(User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }

    /**
     * @return Collection<int, Score>
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(Score $score): static
    {
        if (!$this->scores->contains($score)) {
            $this->scores->add($score);
            $score->setEvent($this);
        }
        return $this;
    }

    public function removeScore(Score $score): static
    {
        if ($this->scores->removeElement($score) && $score->getEvent() === $this) {
            $score->setEvent(null);
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setEvent($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message) && $message->getEvent() === $this) {
            $message->setEvent(null);
        }
        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setEvent($this);
        }
        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image) && $image->getEvent() === $this) {
            $image->setEvent(null);
        }
        return $this;
    }

    public function isAdminApproved(): ?bool
    {
        return $this->isAdminApproved;
    }

    public function setIsAdminApproved(bool $isAdminApproved): self
    {
        $this->isAdminApproved = $isAdminApproved;
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): static
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getValidationStatus(): string
    {
        if ($this->isApproved === null) {
            return 'En attente de validation';
        }

        return $this->isApproved ? 'Validé' : 'Non validé';
    }
}
