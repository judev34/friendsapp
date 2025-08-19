<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Registration;
use App\Entity\RegistrationStatus;
use App\Repository\EventRepository;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\EventCreatedEvent;
use App\Event\EventUpdatedEvent;
use App\Event\EventPublishedEvent;

class EventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private RegistrationRepository $registrationRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Crée un nouvel événement
     */
    public function createEvent(Event $event, User $organizer): Event
    {
        $event->setOrganizer($organizer);
        $event->setCreatedAt(new \DateTime());

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // Dispatch event pour notifications
        $this->eventDispatcher->dispatch(new EventCreatedEvent($event), EventCreatedEvent::NAME);

        return $event;
    }

    /**
     * Met à jour un événement
     */
    public function updateEvent(Event $event): Event
    {
        $event->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        // Dispatch event pour notifications
        $this->eventDispatcher->dispatch(new EventUpdatedEvent($event), EventUpdatedEvent::NAME);

        return $event;
    }

    /**
     * Publie un événement
     */
    public function publishEvent(Event $event): Event
    {
        if (!$event->isPublished()) {
            $event->setIsPublished(true);
            $event->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            // Dispatch event pour notifications
            $this->eventDispatcher->dispatch(new EventPublishedEvent($event), EventPublishedEvent::NAME);
        }

        return $event;
    }

    /**
     * Dépublie un événement
     */
    public function unpublishEvent(Event $event): Event
    {
        if ($event->isPublished()) {
            $event->setIsPublished(false);
            $event->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();
        }

        return $event;
    }

    /**
     * Supprime un événement (soft delete en annulant toutes les inscriptions)
     */
    public function deleteEvent(Event $event): void
    {
        // Annuler toutes les inscriptions confirmées
        $confirmedRegistrations = $this->registrationRepository->findConfirmedByEvent($event);
        foreach ($confirmedRegistrations as $registration) {
            $registration->cancel();
        }

        // Marquer l'événement comme non publié
        $event->setIsPublished(false);
        $event->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();
    }

    /**
     * Recherche d'événements avec filtres
     */
    public function searchEvents(array $filters = [], int $page = 1, int $limit = 20): array
    {
        return $this->eventRepository->findWithFilters($filters, 'startDate', 'ASC', $page, $limit);
    }

    /**
     * Obtient les événements populaires
     */
    public function getPopularEvents(int $limit = 10): array
    {
        return $this->eventRepository->findPopularEvents($limit);
    }

    /**
     * Obtient les événements à venir
     */
    public function getUpcomingEvents(int $limit = null): array
    {
        return $this->eventRepository->findUpcomingPublishedEvents($limit);
    }

    /**
     * Obtient les événements gratuits
     */
    public function getFreeEvents(): array
    {
        return $this->eventRepository->findFreeEvents();
    }

    /**
     * Obtient les événements d'un organisateur
     */
    public function getEventsByOrganizer(User $organizer): array
    {
        return $this->eventRepository->findByOrganizer($organizer);
    }

    /**
     * Vérifie si un utilisateur peut modifier un événement
     */
    public function canUserEditEvent(User $user, Event $event): bool
    {
        // L'organisateur peut toujours modifier
        if ($event->getOrganizer() === $user) {
            return true;
        }

        // Les admins peuvent modifier
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si un événement peut être supprimé
     */
    public function canEventBeDeleted(Event $event): bool
    {
        // Ne peut pas supprimer un événement passé
        if ($event->isPast()) {
            return false;
        }

        // Ne peut pas supprimer un événement avec des inscriptions confirmées
        $confirmedCount = $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::CONFIRMED);
        if ($confirmedCount > 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtient les statistiques d'un événement
     */
    public function getEventStatistics(Event $event): array
    {
        return [
            'total_registrations' => $event->getRegistrations()->count(),
            'confirmed_registrations' => $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::CONFIRMED),
            'pending_registrations' => $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::PENDING),
            'cancelled_registrations' => $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::CANCELLED),
            'waitlist_registrations' => $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::WAITLIST),
            'available_spots' => $event->getAvailableSpots(),
            'is_full' => $event->isFull(),
            'total_revenue' => $this->registrationRepository->getRevenueByEvent($event)
        ];
    }

    /**
     * Duplique un événement
     */
    public function duplicateEvent(Event $originalEvent, User $organizer): Event
    {
        $newEvent = new Event();
        $newEvent->setTitle($originalEvent->getTitle() . ' (Copie)')
            ->setDescription($originalEvent->getDescription())
            ->setLocation($originalEvent->getLocation())
            ->setMaxParticipants($originalEvent->getMaxParticipants())
            ->setPrice($originalEvent->getPrice())
            ->setImageUrl($originalEvent->getImageUrl())
            ->setTags($originalEvent->getTags())
            ->setOrganizer($organizer);

        // Les dates doivent être définies manuellement
        // $newEvent->setStartDate(...)
        // $newEvent->setEndDate(...)

        $this->entityManager->persist($newEvent);
        $this->entityManager->flush();

        return $newEvent;
    }
}
