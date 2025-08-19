<?php

namespace App\Strategy;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailNotificationStrategy implements NotificationStrategyInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = 'noreply@eventapp.com'
    ) {}

    public function send(User $user, string $subject, string $message, array $data = []): bool
    {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject($subject)
                ->html($message);

            // Ajouter des en-têtes personnalisés si nécessaire
            if (isset($data['headers'])) {
                foreach ($data['headers'] as $name => $value) {
                    $email->getHeaders()->addTextHeader($name, $value);
                }
            }

            $this->mailer->send($email);
            
            $this->logger->info('Email envoyé avec succès', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'subject' => $subject
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi d\'email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function supports(string $type): bool
    {
        return $type === 'email';
    }

    public function getName(): string
    {
        return 'email';
    }
}
