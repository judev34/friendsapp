<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Service\RegistrationService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events/{id}/registrations', name: 'api_event_registrations_')]
#[OA\Tag(name: 'Event Registrations')]
class EventRegistrationController extends AbstractController
{
    public function __construct(
        private RegistrationService $registrationService
    ) {}

    #[Route('', name: 'list', methods: ['GET'], requirements: ['id' => '\\d+'])]
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

    #[Route('/confirmed', name: 'confirmed', methods: ['GET'], requirements: ['id' => '\\d+'])]
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

    #[Route('/waitlist', name: 'waitlist', methods: ['GET'], requirements: ['id' => '\\d+'])]
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
