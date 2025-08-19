<?php

namespace App\EventListener;

use App\Event\EventCreatedEvent;
use App\Event\EventPublishedEvent;
use App\Event\EventUpdatedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EventNotificationListener
{
    public function __construct(
        private NotificationService $notificationService,
        private Environment $twig,
        private LoggerInterface $logger
    ) {}

    #[AsEventListener(event: EventCreatedEvent::NAME)]
    public function onEventCreated(EventCreatedEvent $event): void
    {
        $eventEntity = $event->getEvent();
        $organizer = $eventEntity->getOrganizer();

        // Notification à l'organisateur
        $subject = 'Événement créé avec succès';
        $message = $this->twig->render('emails/event_created.html.twig', [
            'event' => $eventEntity,
            'organizer' => $organizer
        ]);

        $this->notificationService->send('email', $organizer, $subject, $message);

        // Notification Slack pour les admins
        $slackMessage = sprintf(
            'Nouvel événement créé : "%s" par %s',
            $eventEntity->getTitle(),
            $organizer->getFullName()
        );

        $this->notificationService->send('slack', $organizer, $subject, $slackMessage, [
            'color' => 'good'
        ]);
    }

    #[AsEventListener(event: EventPublishedEvent::NAME)]
    public function onEventPublished(EventPublishedEvent $event): void
    {
        $eventEntity = $event->getEvent();
        $organizer = $eventEntity->getOrganizer();

        // Notification à l'organisateur
        $subject = 'Événement publié';
        $message = $this->twig->render('emails/event_published.html.twig', [
            'event' => $eventEntity,
            'organizer' => $organizer
        ]);

        $this->notificationService->send('email', $organizer, $subject, $message);

        // Notifier les utilisateurs abonnés aux notifications d'événements
        // TODO: Implémenter système d'abonnements utilisateurs pour notifications d'événements
        $this->logger->info('Événement publié - notifications à implémenter', [
            'event_id' => $eventEntity->getId(),
            'event_title' => $eventEntity->getTitle()
        ]);
    }

    #[AsEventListener(event: EventUpdatedEvent::NAME)]
    public function onEventUpdated(EventUpdatedEvent $event): void
    {
        $eventEntity = $event->getEvent();

        // Notifier tous les participants inscrits des modifications
        foreach ($eventEntity->getRegistrations() as $registration) {
            if ($registration->isConfirmed()) {
                $subject = 'Modification de l\'événement : ' . $eventEntity->getTitle();
                $message = $this->twig->render('emails/event_updated.html.twig', [
                    'event' => $eventEntity,
                    'registration' => $registration
                ]);

                $this->notificationService->send('email', $registration->getUser(), $subject, $message);
            }
        }
    }
}
