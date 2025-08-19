<?php

namespace App\Event;

use App\Entity\Registration;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class RegistrationConfirmedEvent extends SymfonyEvent
{
    public const NAME = 'registration.confirmed';

    public function __construct(
        private Registration $registration
    ) {}

    public function getRegistration(): Registration
    {
        return $this->registration;
    }
}
