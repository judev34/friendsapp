<?php

namespace App\Event;

use App\Entity\Event;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class EventCreatedEvent extends SymfonyEvent
{
    public const NAME = 'event.created';

    public function __construct(
        private Event $event
    ) {}

    public function getEvent(): Event
    {
        return $this->event;
    }
}
