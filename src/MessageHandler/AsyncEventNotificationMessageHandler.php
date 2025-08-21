<?php

namespace App\MessageHandler;

use App\Message\AsyncEventNotificationMessage;
use App\Repository\EventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler pour traiter les notifications d'événements asynchrones
 * Traite les messages AsyncEventNotificationMessage en arrière-plan
 */
#[AsMessageHandler]
class AsyncEventNotificationMessageHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(AsyncEventNotificationMessage $message): void
    {
        $eventId = $message->getEventId();
        $action = $message->getAction();

        $this->logger->info('Traitement notification asynchrone', [
            'event_id' => $eventId,
            'action' => $action
        ]);

        $event = $this->eventRepository->find($eventId);
        
        if (!$event) {
            $this->logger->warning('Événement non trouvé pour notification', [
                'event_id' => $eventId
            ]);
            return;
        }

        // Ici vous pouvez ajouter la logique de notification
        // Par exemple : envoi d'emails, notifications push, etc.
        
        switch ($action) {
            case 'created':
                $this->handleEventCreated($event);
                break;
            case 'updated':
                $this->handleEventUpdated($event);
                break;
            case 'published':
                $this->handleEventPublished($event);
                break;
            default:
                $this->logger->warning('Action inconnue pour notification', [
                    'action' => $action,
                    'event_id' => $eventId
                ]);
        }
    }

    private function handleEventCreated($event): void
    {
        // Logique pour événement créé
        $this->logger->info('Notification événement créé', [
            'event_id' => $event->getId(),
            'title' => $event->getTitle()
        ]);
    }

    private function handleEventUpdated($event): void
    {
        // Logique pour événement mis à jour
        $this->logger->info('Notification événement mis à jour', [
            'event_id' => $event->getId(),
            'title' => $event->getTitle()
        ]);
    }

    private function handleEventPublished($event): void
    {
        // Logique pour événement publié
        $this->logger->info('Notification événement publié', [
            'event_id' => $event->getId(),
            'title' => $event->getTitle()
        ]);
    }
}
