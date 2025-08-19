<?php

namespace App\Event;

use App\Entity\Event;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class EventPublishedEvent extends SymfonyEvent
{
    public const NAME = 'event.published';

    public function __construct(
        private Event $event
    ) {}

    public function getEvent(): Event
    {
        return $this->event;
    }
}
