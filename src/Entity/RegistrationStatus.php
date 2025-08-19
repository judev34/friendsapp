<?php

namespace App\Entity;

enum RegistrationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case WAITLIST = 'waitlist';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmée',
            self::CANCELLED => 'Annulée',
            self::WAITLIST => 'Liste d\'attente',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::WAITLIST => 'info',
        };
    }
}
