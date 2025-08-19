<?php

namespace App\EventListener;

use App\Event\UserRegisteredEvent;
use App\Event\RegistrationConfirmedEvent;
use App\Event\RegistrationCancelledEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Twig\Environment;

class RegistrationNotificationListener
{
    public function __construct(
        private NotificationService $notificationService,
        private Environment $twig
    ) {}

    #[AsEventListener(event: UserRegisteredEvent::NAME)]
    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $registration = $event->getRegistration();
        $user = $registration->getUser();
        $eventEntity = $registration->getEvent();

        // Notification à l'utilisateur
        $subject = 'Inscription à l\'événement : ' . $eventEntity->getTitle();
        $message = $this->twig->render('emails/user_registered.html.twig', [
            'registration' => $registration,
            'event' => $eventEntity,
            'user' => $user
        ]);

        $this->notificationService->send('email', $user, $subject, $message);

        // Notification à l'organisateur
        $organizerSubject = 'Nouvelle inscription : ' . $eventEntity->getTitle();
        $organizerMessage = $this->twig->render('emails/organizer_new_registration.html.twig', [
            'registration' => $registration,
            'event' => $eventEntity,
            'organizer' => $eventEntity->getOrganizer()
        ]);

        $this->notificationService->send('email', $eventEntity->getOrganizer(), $organizerSubject, $organizerMessage);
    }

    #[AsEventListener(event: RegistrationConfirmedEvent::NAME)]
    public function onRegistrationConfirmed(RegistrationConfirmedEvent $event): void
    {
        $registration = $event->getRegistration();
        $user = $registration->getUser();
        $eventEntity = $registration->getEvent();

        // Notification de confirmation à l'utilisateur
        $subject = 'Inscription confirmée : ' . $eventEntity->getTitle();
        $message = $this->twig->render('emails/registration_confirmed.html.twig', [
            'registration' => $registration,
            'event' => $eventEntity,
            'user' => $user
        ]);

        $this->notificationService->send('email', $user, $subject, $message);

        // TODO: Générer le billet PDF et l'envoyer par email
    }

    #[AsEventListener(event: RegistrationCancelledEvent::NAME)]
    public function onRegistrationCancelled(RegistrationCancelledEvent $event): void
    {
        $registration = $event->getRegistration();
        $user = $registration->getUser();
        $eventEntity = $registration->getEvent();

        // Notification d'annulation à l'utilisateur
        $subject = 'Inscription annulée : ' . $eventEntity->getTitle();
        $message = $this->twig->render('emails/registration_cancelled.html.twig', [
            'registration' => $registration,
            'event' => $eventEntity,
            'user' => $user
        ]);

        $this->notificationService->send('email', $user, $subject, $message);

        // Notification à l'organisateur
        $organizerSubject = 'Annulation d\'inscription : ' . $eventEntity->getTitle();
        $organizerMessage = $this->twig->render('emails/organizer_registration_cancelled.html.twig', [
            'registration' => $registration,
            'event' => $eventEntity,
            'organizer' => $eventEntity->getOrganizer()
        ]);

        $this->notificationService->send('email', $eventEntity->getOrganizer(), $organizerSubject, $organizerMessage);
    }
}
