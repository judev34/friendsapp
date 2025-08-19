<?php

namespace App\Service;

use App\Entity\User;
use App\Strategy\NotificationStrategyInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    /** @var NotificationStrategyInterface[] */
    private array $strategies = [];

    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function addStrategy(NotificationStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * Envoie une notification via une stratégie spécifique
     */
    public function send(string $strategyName, User $user, string $subject, string $message, array $data = []): bool
    {
        if (!isset($this->strategies[$strategyName])) {
            $this->logger->error('Stratégie de notification non trouvée', [
                'strategy' => $strategyName,
                'available_strategies' => array_keys($this->strategies)
            ]);
            return false;
        }

        return $this->strategies[$strategyName]->send($user, $subject, $message, $data);
    }

    /**
     * Envoie une notification via toutes les stratégies supportées
     */
    public function sendToAll(User $user, string $subject, string $message, array $data = []): array
    {
        $results = [];
        
        foreach ($this->strategies as $name => $strategy) {
            $results[$name] = $strategy->send($user, $subject, $message, $data);
        }

        return $results;
    }

    /**
     * Envoie une notification via les stratégies spécifiées
     */
    public function sendToMultiple(array $strategyNames, User $user, string $subject, string $message, array $data = []): array
    {
        $results = [];
        
        foreach ($strategyNames as $strategyName) {
            $results[$strategyName] = $this->send($strategyName, $user, $subject, $message, $data);
        }

        return $results;
    }

    /**
     * Obtient la liste des stratégies disponibles
     */
    public function getAvailableStrategies(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Vérifie si une stratégie est disponible
     */
    public function hasStrategy(string $strategyName): bool
    {
        return isset($this->strategies[$strategyName]);
    }
}
