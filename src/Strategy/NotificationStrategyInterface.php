<?php

namespace App\Strategy;

use App\Entity\User;

interface NotificationStrategyInterface
{
    public function send(User $user, string $subject, string $message, array $data = []): bool;
    
    public function supports(string $type): bool;
    
    public function getName(): string;
}
