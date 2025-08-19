<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Registration;
use App\Service\RegistrationService;
use App\Repository\RegistrationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api/registrations', name: 'api_registrations_')]
#[OA\Tag(name: 'Registrations')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private RegistrationService $registrationService,
        private RegistrationRepository $registrationRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/registrations',
        summary: 'Mes inscriptions',
        responses: [
            new OA\Response(response: 200, description: 'Liste des inscriptions'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $registrations = $this->registrationService->getUserRegistrations($user);

        return $this->json($registrations, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }

    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/registrations/upcoming',
        summary: 'Mes prochaines inscriptions',
        responses: [
            new OA\Response(response: 200, description: 'Prochaines inscriptions'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function upcoming(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $registrations = $this->registrationService->getUserUpcomingRegistrations($user);

        return $this->json($registrations, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }

    #[Route('/events/{id}', name: 'register', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        path: '/api/registrations/events/{id}',
        summary: 'S\'inscrire à un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 201, description: 'Inscription créée'),
            new OA\Response(response: 400, description: 'Inscription impossible'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function register(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->registrationService->canUserRegister($user, $event)) {
            return $this->json(['error' => 'Inscription impossible'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $registration = $this->registrationService->registerUserToEvent($user, $event);
            
            return $this->json($registration, Response::HTTP_CREATED, [], ['groups' => ['registration:read']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/confirm', name: 'confirm', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Patch(
        path: '/api/registrations/{id}/confirm',
        summary: 'Confirmer une inscription',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inscription confirmée'),
            new OA\Response(response: 400, description: 'Confirmation impossible'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Inscription non trouvée')
        ]
    )]
    public function confirm(Registration $registration): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur peut confirmer cette inscription
        if ($registration->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $registration = $this->registrationService->confirmRegistration($registration);
            
            return $this->json($registration, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Patch(
        path: '/api/registrations/{id}/cancel',
        summary: 'Annuler une inscription',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inscription annulée'),
            new OA\Response(response: 400, description: 'Annulation impossible'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Inscription non trouvée')
        ]
    )]
    public function cancel(Registration $registration): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->registrationService->canUserCancelRegistration($user, $registration)) {
            return $this->json(['error' => 'Annulation impossible'], Response::HTTP_BAD_REQUEST);
        }

        $registration = $this->registrationService->cancelRegistration($registration);

        return $this->json($registration, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/registrations/{id}',
        summary: 'Détails d\'une inscription',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails de l\'inscription'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Inscription non trouvée')
        ]
    )]
    public function show(Registration $registration): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier l'accès
        if ($registration->getUser() !== $user && 
            $registration->getEvent()->getOrganizer() !== $user && 
            !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($registration, Response::HTTP_OK, [], ['groups' => ['registration:read', 'registration:details']]);
    }

    #[Route('/ticket/{ticketCode}', name: 'by_ticket', methods: ['GET'])]
    #[OA\Get(
        path: '/api/registrations/ticket/{ticketCode}',
        summary: 'Inscription par code de billet',
        parameters: [
            new OA\Parameter(name: 'ticketCode', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inscription trouvée'),
            new OA\Response(response: 404, description: 'Billet non trouvé')
        ]
    )]
    public function byTicket(string $ticketCode): JsonResponse
    {
        $registration = $this->registrationService->findByTicketCode($ticketCode);

        if (!$registration) {
            return $this->json(['error' => 'Billet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($registration, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }
}

#[Route('/api/events/{id}/registrations', name: 'api_event_registrations_')]
#[OA\Tag(name: 'Event Registrations')]
class EventRegistrationController extends AbstractController
{
    public function __construct(
        private RegistrationService $registrationService
    ) {}

    #[Route('', name: 'list', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/events/{id}/registrations',
        summary: 'Inscriptions d\'un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des inscriptions'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function list(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Seul l'organisateur ou un admin peut voir les inscriptions
        if ($event->getOrganizer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $confirmedRegistrations = $this->registrationService->getConfirmedRegistrations($event);
        $waitlistRegistrations = $this->registrationService->getWaitlistRegistrations($event);

        return $this->json([
            'confirmed' => $confirmedRegistrations,
            'waitlist' => $waitlistRegistrations
        ], Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }

    #[Route('/confirmed', name: 'confirmed', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/events/{id}/registrations/confirmed',
        summary: 'Inscriptions confirmées d\'un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inscriptions confirmées'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function confirmed(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $registrations = $this->registrationService->getConfirmedRegistrations($event);

        return $this->json($registrations, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }

    #[Route('/waitlist', name: 'waitlist', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/events/{id}/registrations/waitlist',
        summary: 'Liste d\'attente d\'un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste d\'attente'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function waitlist(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $registrations = $this->registrationService->getWaitlistRegistrations($event);

        return $this->json($registrations, Response::HTTP_OK, [], ['groups' => ['registration:read']]);
    }
}
