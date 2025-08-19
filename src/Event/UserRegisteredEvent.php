<?php

namespace App\Event;

use App\Entity\Registration;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class UserRegisteredEvent extends SymfonyEvent
{
    public const NAME = 'user.registered';

    public function __construct(
        private Registration $registration
    ) {}

    public function getRegistration(): Registration
    {
        return $this->registration;
    }
}
