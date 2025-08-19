<?php

namespace App\Strategy;

use App\Entity\User;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SlackNotificationStrategy implements NotificationStrategyInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $webhookUrl = ''
    ) {}

    public function send(User $user, string $subject, string $message, array $data = []): bool
    {
        if (empty($this->webhookUrl)) {
            $this->logger->warning('Webhook Slack non configuré');
            return false;
        }

        try {
            $payload = [
                'text' => $subject,
                'attachments' => [
                    [
                        'color' => $data['color'] ?? 'good',
                        'fields' => [
                            [
                                'title' => 'Utilisateur',
                                'value' => $user->getFullName(),
                                'short' => true
                            ],
                            [
                                'title' => 'Email',
                                'value' => $user->getEmail(),
                                'short' => true
                            ],
                            [
                                'title' => 'Message',
                                'value' => $message,
                                'short' => false
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->httpClient->request('POST', $this->webhookUrl, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $this->logger->info('Notification Slack envoyée avec succès', [
                    'user_id' => $user->getId(),
                    'subject' => $subject
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de notification Slack', [
                'user_id' => $user->getId(),
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function supports(string $type): bool
    {
        return $type === 'slack';
    }

    public function getName(): string
    {
        return 'slack';
    }
}
