<?php

namespace App\Event;

use App\Entity\Event;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class EventUpdatedEvent extends SymfonyEvent
{
    public const NAME = 'event.updated';

    public function __construct(
        private Event $event
    ) {}

    public function getEvent(): Event
    {
        return $this->event;
    }
}
