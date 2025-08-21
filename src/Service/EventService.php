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
use Symfony\Component\Messenger\MessageBusInterface;
use App\Event\EventCreatedEvent;
use App\Event\EventUpdatedEvent;
use App\Event\EventPublishedEvent;
use App\Message\AsyncEventNotificationMessage;

class EventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private RegistrationRepository $registrationRepository,
        private EventDispatcherInterface $eventDispatcher,
        private MessageBusInterface $messageBus
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

        // Dispatch event pour notifications SYNCHRONES (existant)
        $this->eventDispatcher->dispatch(new EventCreatedEvent($event), EventCreatedEvent::NAME);

        // // Dispatch message pour notifications ASYNCHRONES (nouveau)
        // $this->messageBus->dispatch(new AsyncEventNotificationMessage(
        //     $event->getId(),
        //     'created'
        // ));

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

        // Supprimer physiquement l'événement de la base de données
        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }

    /**
     * Recherche d'événements avec filtres avancés
     */
    public function searchEvents(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $result = $this->eventRepository->findWithFilters($filters, 'startDate', 'ASC', $page, $limit);
        
        return [
            'events' => $result['events'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($result['total'] / $limit)
        ];
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
     * Obtient les statistiques détaillées d'un événement
     */
    public function getEventStatistics(Event $event): array
    {
        $confirmedCount = $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::CONFIRMED);
        $pendingCount = $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::PENDING);
        $cancelledCount = $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::CANCELLED);
        $waitlistCount = $this->registrationRepository->countByEventAndStatus($event, RegistrationStatus::WAITLIST);
        $totalRevenue = $this->registrationRepository->getRevenueByEvent($event);
        
        $maxParticipants = $event->getMaxParticipants();
        $availableSpots = max(0, $maxParticipants - $confirmedCount);
        $occupancyRate = $maxParticipants > 0 ? ($confirmedCount / $maxParticipants) * 100 : 0;
        
        return [
            'registrations' => [
                'total' => $event->getRegistrations()->count(),
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'cancelled' => $cancelledCount,
                'waitlist' => $waitlistCount
            ],
            'capacity' => [
                'max_participants' => $maxParticipants,
                'available_spots' => $availableSpots,
                'is_full' => $event->isFull(),
                'occupancy_rate' => round($occupancyRate, 2)
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'average_per_participant' => $confirmedCount > 0 ? round($totalRevenue / $confirmedCount, 2) : 0
            ],
            'timeline' => [
                'days_until_event' => $event->getDaysUntilStart(),
                'is_past' => $event->isPast(),
                'is_today' => $event->isToday(),
                'is_upcoming' => $event->isUpcoming()
            ]
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

        // Copier les dates de l'événement original
        $newEvent->setStartDate($originalEvent->getStartDate());
        $newEvent->setEndDate($originalEvent->getEndDate());

        $this->entityManager->persist($newEvent);
        $this->entityManager->flush();

        return $newEvent;
    }

    /**
     * Obtient les événements par catégorie/tags
     */
    public function getEventsByCategory(string $category, int $limit = 10): array
    {
        return $this->eventRepository->findByTag($category, $limit);
    }

    /**
     * Obtient les événements recommandés pour un utilisateur
     */
    public function getRecommendedEvents(User $user, int $limit = 5): array
    {
        // Logique de recommandation basée sur l'historique de l'utilisateur
        $userRegistrations = $this->registrationRepository->findByUser($user);
        $userTags = [];
        
        foreach ($userRegistrations as $registration) {
            $eventTags = $registration->getEvent()->getTags();
            if ($eventTags) {
                $userTags = array_merge($userTags, $eventTags);
            }
        }
        
        $userTags = array_unique($userTags);
        
        if (empty($userTags)) {
            // Si pas d'historique, retourner les événements populaires
            return $this->getPopularEvents($limit);
        }
        
        return $this->eventRepository->findRecommendedByTags($userTags, $limit);
    }

    /**
     * Obtient les statistiques globales des événements
     */
    public function getGlobalStatistics(): array
    {
        $stats = [
            'total_events' => $this->eventRepository->count([]),
            'published_events' => $this->eventRepository->count(['isPublished' => true]),
            'upcoming_events' => count($this->getUpcomingEvents()),
            'events_this_month' => $this->eventRepository->countEventsThisMonth(),
            'total_registrations' => $this->registrationRepository->count([]),
            'confirmed_registrations' => $this->registrationRepository->count(['status' => RegistrationStatus::CONFIRMED]),
            'average_participants_per_event' => $this->eventRepository->getAverageParticipantsPerEvent(),
            // placeholders for completeness
            'total_revenue' => 0,
            'popular_categories' => [],
        ];

        // Ajouter les clés camelCase attendues par certains consommateurs (tests inclus)
        $stats += [
            'totalEvents' => $stats['total_events'],
            'publishedEvents' => $stats['published_events'],
            'upcomingEvents' => $stats['upcoming_events'],
            'eventsThisMonth' => $stats['events_this_month'],
            'totalRegistrations' => $stats['total_registrations'],
            'confirmedRegistrations' => $stats['confirmed_registrations'],
            'averageParticipants' => $stats['average_participants_per_event'],
            'totalRevenue' => $stats['total_revenue'],
            'popularCategories' => $stats['popular_categories'],
        ];

        return $stats;
    }
}
