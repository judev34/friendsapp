<?php

namespace App\Message;

/**
 * Message pour les notifications d'événements asynchrones
 * Utilisé par Symfony Messenger pour traiter les notifications en arrière-plan
 */
class AsyncEventNotificationMessage
{
    public function __construct(
        private int $eventId,
        private string $action
    ) {
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
