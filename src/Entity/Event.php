<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['event:read', 'registration:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit faire au moins 3 caractères.')]
    #[Groups(['event:read', 'event:write', 'registration:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(min: 10, minMessage: 'La description doit faire au moins 10 caractères.')]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    #[Assert\GreaterThan('today', message: 'La date de début doit être dans le futur.')]
    #[Groups(['event:read', 'event:write', 'registration:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: 'La date de fin doit être après la date de début.')]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
    #[Groups(['event:read', 'event:write', 'registration:read'])]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Le nombre maximum de participants doit être positif.')]
    #[Groups(['event:read', 'event:write'])]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul.')]
    #[Groups(['event:read', 'event:write'])]
    private ?string $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    #[Groups(['event:read'])]
    private bool $isPublished = false;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read'])]
    private ?User $organizer = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Registration::class, orphanRemoval: true)]
    private Collection $registrations;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?array $tags = null;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->tags = [];
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
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
     * @return Collection<int, Registration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getConfirmedRegistrationsCount(): int
    {
        return $this->registrations->filter(
            fn(Registration $registration) => $registration->getStatus() === RegistrationStatus::CONFIRMED
        )->count();
    }

    public function getAvailableSpots(): ?int
    {
        if ($this->maxParticipants === null) {
            return null;
        }

        return $this->maxParticipants - $this->getConfirmedRegistrationsCount();
    }

    public function isFull(): bool
    {
        if ($this->maxParticipants === null) {
            return false;
        }

        return $this->getConfirmedRegistrationsCount() >= $this->maxParticipants;
    }

    public function isPast(): bool
    {
        return $this->endDate < new \DateTime();
    }

    public function isUpcoming(): bool
    {
        return $this->startDate > new \DateTime();
    }

    public function isOngoing(): bool
    {
        $now = new \DateTime();
        return $this->startDate <= $now && $this->endDate >= $now;
    }

    public function getDuration(): \DateInterval
    {
        return $this->startDate->diff($this->endDate);
    }

    public function isFree(): bool
    {
        return $this->price === null || $this->price === '0.00';
    }
}
