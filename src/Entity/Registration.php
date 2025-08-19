<?php

namespace App\Entity;

use App\Repository\RegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_event', columns: ['user_id', 'event_id'])]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['registration:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['registration:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['registration:read'])]
    private ?Event $event = null;

    #[ORM\Column(type: Types::STRING, enumType: RegistrationStatus::class)]
    #[Groups(['registration:read'])]
    private ?RegistrationStatus $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['registration:read'])]
    private ?\DateTimeInterface $registeredAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $confirmedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['registration:read', 'registration:details'])]
    private ?string $ticketCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    #[Groups(['registration:details'])]
    private ?string $paidAmount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['registration:details'])]
    private ?\DateTimeInterface $paidAt = null;

    public function __construct()
    {
        $this->registeredAt = new \DateTime();
        $this->status = RegistrationStatus::PENDING;
        $this->generateTicketCode();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getStatus(): ?RegistrationStatus
    {
        return $this->status;
    }

    public function setStatus(RegistrationStatus $status): static
    {
        $this->status = $status;

        // Mettre à jour les timestamps selon le statut
        if ($status === RegistrationStatus::CONFIRMED && $this->confirmedAt === null) {
            $this->confirmedAt = new \DateTime();
        } elseif ($status === RegistrationStatus::CANCELLED && $this->cancelledAt === null) {
            $this->cancelledAt = new \DateTime();
        }

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeInterface $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeInterface
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeInterface $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getTicketCode(): ?string
    {
        return $this->ticketCode;
    }

    public function setTicketCode(?string $ticketCode): static
    {
        $this->ticketCode = $ticketCode;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPaidAmount(): ?string
    {
        return $this->paidAmount;
    }

    public function setPaidAmount(?string $paidAmount): static
    {
        $this->paidAmount = $paidAmount;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function confirm(): static
    {
        $this->setStatus(RegistrationStatus::CONFIRMED);
        return $this;
    }

    public function cancel(): static
    {
        $this->setStatus(RegistrationStatus::CANCELLED);
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === RegistrationStatus::CANCELLED;
    }

    public function isPending(): bool
    {
        return $this->status === RegistrationStatus::PENDING;
    }

    public function isOnWaitlist(): bool
    {
        return $this->status === RegistrationStatus::WAITLIST;
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null && $this->paidAmount !== null;
    }

    public function markAsPaid(string $amount): static
    {
        $this->paidAmount = $amount;
        $this->paidAt = new \DateTime();
        
        return $this;
    }

    private function generateTicketCode(): void
    {
        $this->ticketCode = strtoupper(bin2hex(random_bytes(6)));
    }

    public function regenerateTicketCode(): static
    {
        $this->generateTicketCode();
        return $this;
    }
}
