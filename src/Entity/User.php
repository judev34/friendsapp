<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte avec cet email existe déjà.')]
#[OA\Schema(
    schema: 'User',
    description: 'Utilisateur de l\'application',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            description: 'Identifiant unique de l\'utilisateur',
            example: 1
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            description: 'Adresse email de l\'utilisateur',
            example: 'user@example.com'
        ),
        new OA\Property(
            property: 'firstName',
            type: 'string',
            description: 'Prénom de l\'utilisateur',
            example: 'Jean'
        ),
        new OA\Property(
            property: 'lastName',
            type: 'string',
            description: 'Nom de famille de l\'utilisateur',
            example: 'Dupont'
        ),
        new OA\Property(
            property: 'createdAt',
            type: 'string',
            format: 'date-time',
            description: 'Date de création du compte',
            example: '2024-01-15T10:30:00Z'
        ),
        new OA\Property(
            property: 'isVerified',
            type: 'boolean',
            description: 'Statut de vérification du compte',
            example: true
        )
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'event:read', 'registration:read'])]
    #[OA\Property(description: 'Identifiant unique de l\'utilisateur', example: 1)]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email doit être valide.')]
    #[Groups(['user:read', 'user:profile'])]
    #[OA\Property(description: 'Adresse email de l\'utilisateur', format: 'email', example: 'user@example.com')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit faire au moins 2 caractères.')]
    #[Groups(['user:read', 'event:read', 'registration:read'])]
    #[OA\Property(description: 'Prénom de l\'utilisateur', example: 'Jean')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit faire au moins 2 caractères.')]
    #[Groups(['user:read', 'event:read', 'registration:read'])]
    #[OA\Property(description: 'Nom de famille de l\'utilisateur', example: 'Dupont')]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:profile'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    #[Groups(['user:profile'])]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'organizer')]
    private Collection $organizedEvents;

    /**
     * @var Collection<int, Registration>
     */
    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'user')]
    private Collection $registrations;

    public function __construct()
    {
        $this->organizedEvents = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Groups(['user:read', 'event:read', 'registration:read'])]
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getOrganizedEvents(): Collection
    {
        return $this->organizedEvents;
    }

    public function addOrganizedEvent(Event $organizedEvent): static
    {
        if (!$this->organizedEvents->contains($organizedEvent)) {
            $this->organizedEvents->add($organizedEvent);
            $organizedEvent->setOrganizer($this);
        }

        return $this;
    }

    public function removeOrganizedEvent(Event $organizedEvent): static
    {
        if ($this->organizedEvents->removeElement($organizedEvent)) {
            // set the owning side to null (unless already changed)
            if ($organizedEvent->getOrganizer() === $this) {
                $organizedEvent->setOrganizer(null);
            }
        }

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
            $registration->setUser($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getUser() === $this) {
                $registration->setUser(null);
            }
        }

        return $this;
    }

    public function isRegisteredToEvent(Event $event): bool
    {
        foreach ($this->registrations as $registration) {
            if ($registration->getEvent() === $event && $registration->getStatus() === RegistrationStatus::CONFIRMED) {
                return true;
            }
        }

        return false;
    }
}
