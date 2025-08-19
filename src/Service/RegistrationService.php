<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Registration;
use App\Entity\RegistrationStatus;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\UserRegisteredEvent;
use App\Event\RegistrationConfirmedEvent;
use App\Event\RegistrationCancelledEvent;

class RegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RegistrationRepository $registrationRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Inscrit un utilisateur à un événement
     */
    public function registerUserToEvent(User $user, Event $event): Registration
    {
        // Vérifier si l'utilisateur est déjà inscrit
        $existingRegistration = $this->registrationRepository->findByUserAndEvent($user, $event);
        if ($existingRegistration) {
            throw new \InvalidArgumentException('L\'utilisateur est déjà inscrit à cet événement.');
        }

        // Vérifier si l'événement est publié
        if (!$event->isPublished()) {
            throw new \InvalidArgumentException('Impossible de s\'inscrire à un événement non publié.');
        }

        // Vérifier si l'événement n'est pas passé
        if ($event->isPast()) {
            throw new \InvalidArgumentException('Impossible de s\'inscrire à un événement passé.');
        }

        $registration = new Registration();
        $registration->setUser($user)
            ->setEvent($event);

        // Déterminer le statut initial
        if ($event->isFull()) {
            $registration->setStatus(RegistrationStatus::WAITLIST);
        } else {
            $registration->setStatus(RegistrationStatus::PENDING);
        }

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        // Dispatch event pour notifications
        $this->eventDispatcher->dispatch(new UserRegisteredEvent($registration), UserRegisteredEvent::NAME);

        return $registration;
    }

    /**
     * Confirme une inscription
     */
    public function confirmRegistration(Registration $registration): Registration
    {
        if ($registration->isConfirmed()) {
            return $registration;
        }

        // Vérifier s'il y a encore de la place
        if ($registration->getEvent()->isFull() && !$registration->isOnWaitlist()) {
            throw new \InvalidArgumentException('L\'événement est complet.');
        }

        $registration->confirm();
        $this->entityManager->flush();

        // Dispatch event pour notifications
        $this->eventDispatcher->dispatch(new RegistrationConfirmedEvent($registration), RegistrationConfirmedEvent::NAME);

        return $registration;
    }

    /**
     * Annule une inscription
     */
    public function cancelRegistration(Registration $registration): Registration
    {
        if ($registration->isCancelled()) {
            return $registration;
        }

        $wasConfirmed = $registration->isConfirmed();
        $event = $registration->getEvent();

        $registration->cancel();
        $this->entityManager->flush();

        // Si c'était une inscription confirmée, promouvoir quelqu'un de la liste d'attente
        if ($wasConfirmed) {
            $this->promoteFromWaitlist($event);
        }

        // Dispatch event pour notifications
        $this->eventDispatcher->dispatch(new RegistrationCancelledEvent($registration), RegistrationCancelledEvent::NAME);

        return $registration;
    }

    /**
     * Promeut une inscription de la liste d'attente
     */
    public function promoteFromWaitlist(Event $event): ?Registration
    {
        if ($event->isFull()) {
            return null;
        }

        $nextWaitlistRegistration = $this->registrationRepository->getNextWaitlistRegistration($event);
        if (!$nextWaitlistRegistration) {
            return null;
        }

        $nextWaitlistRegistration->setStatus(RegistrationStatus::PENDING);
        $this->entityManager->flush();

        // Dispatch event pour notification
        $this->eventDispatcher->dispatch(new UserRegisteredEvent($nextWaitlistRegistration), UserRegisteredEvent::NAME);

        return $nextWaitlistRegistration;
    }

    /**
     * Marque une inscription comme payée
     */
    public function markAsPaid(Registration $registration, string $amount): Registration
    {
        $registration->markAsPaid($amount);
        $this->entityManager->flush();

        return $registration;
    }

    /**
     * Obtient les inscriptions d'un utilisateur
     */
    public function getUserRegistrations(User $user): array
    {
        return $this->registrationRepository->findByUser($user);
    }

    /**
     * Obtient les prochaines inscriptions d'un utilisateur
     */
    public function getUserUpcomingRegistrations(User $user): array
    {
        return $this->registrationRepository->findUpcomingByUser($user);
    }

    /**
     * Obtient les inscriptions confirmées pour un événement
     */
    public function getConfirmedRegistrations(Event $event): array
    {
        return $this->registrationRepository->findConfirmedByEvent($event);
    }

    /**
     * Obtient la liste d'attente pour un événement
     */
    public function getWaitlistRegistrations(Event $event): array
    {
        return $this->registrationRepository->findWaitlistByEvent($event);
    }

    /**
     * Vérifie si un utilisateur peut s'inscrire à un événement
     */
    public function canUserRegister(User $user, Event $event): bool
    {
        // Vérifier si déjà inscrit
        $existingRegistration = $this->registrationRepository->findByUserAndEvent($user, $event);
        if ($existingRegistration && !$existingRegistration->isCancelled()) {
            return false;
        }

        // Vérifier si l'événement est publié
        if (!$event->isPublished()) {
            return false;
        }

        // Vérifier si l'événement n'est pas passé
        if ($event->isPast()) {
            return false;
        }

        // L'organisateur ne peut pas s'inscrire à son propre événement
        if ($event->getOrganizer() === $user) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si un utilisateur peut annuler son inscription
     */
    public function canUserCancelRegistration(User $user, Registration $registration): bool
    {
        // Seul l'utilisateur concerné peut annuler
        if ($registration->getUser() !== $user) {
            return false;
        }

        // Ne peut pas annuler une inscription déjà annulée
        if ($registration->isCancelled()) {
            return false;
        }

        // Ne peut pas annuler après que l'événement ait commencé
        if ($registration->getEvent()->isOngoing() || $registration->getEvent()->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Trouve une inscription par code de billet
     */
    public function findByTicketCode(string $ticketCode): ?Registration
    {
        return $this->registrationRepository->findByTicketCode($ticketCode);
    }

    /**
     * Génère un nouveau code de billet
     */
    public function regenerateTicketCode(Registration $registration): Registration
    {
        $registration->regenerateTicketCode();
        $this->entityManager->flush();

        return $registration;
    }

    /**
     * Obtient les statistiques des inscriptions
     */
    public function getRegistrationStatistics(): array
    {
        return $this->registrationRepository->getRegistrationStats();
    }

    /**
     * Nettoie les inscriptions en attente anciennes
     */
    public function cleanupOldPendingRegistrations(int $days = 7): int
    {
        $oldRegistrations = $this->registrationRepository->findPendingOlderThan($days);
        $count = 0;

        foreach ($oldRegistrations as $registration) {
            $registration->cancel();
            $count++;
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * Transfère une inscription vers un autre utilisateur
     */
    public function transferRegistration(Registration $registration, User $newUser): Registration
    {
        // Vérifier que le nouvel utilisateur n'est pas déjà inscrit
        $existingRegistration = $this->registrationRepository->findByUserAndEvent($newUser, $registration->getEvent());
        if ($existingRegistration && !$existingRegistration->isCancelled()) {
            throw new \InvalidArgumentException('Le nouvel utilisateur est déjà inscrit à cet événement.');
        }

        $registration->setUser($newUser);
        $registration->regenerateTicketCode();
        $this->entityManager->flush();

        return $registration;
    }
}
