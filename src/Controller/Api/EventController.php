<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Service\EventService;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/events', name: 'api_events_')]
#[OA\Tag(name: 'Events')]
class EventController extends AbstractController
{
    public function __construct(
        private EventService $eventService,
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/events',
        summary: 'Liste des événements',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'location', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'startDate', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'endDate', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'isFree', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'tags', in: 'query', schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string')))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des événements')
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        
        $filters = [];
        if ($location = $request->query->get('location')) {
            $filters['location'] = $location;
        }
        if ($startDate = $request->query->get('startDate')) {
            $filters['startDate'] = new \DateTime($startDate);
        }
        if ($endDate = $request->query->get('endDate')) {
            $filters['endDate'] = new \DateTime($endDate);
        }
        if ($request->query->has('isFree')) {
            $filters['isFree'] = $request->query->getBoolean('isFree');
        }
        if ($tags = $request->query->all('tags')) {
            $filters['tags'] = $tags;
        }

        $events = $this->eventService->searchEvents($filters, $page, $limit);

        return $this->json([
            'data' => $events,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($events)
            ]
        ], Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/events/{id}',
        summary: 'Détails d\'un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails de l\'événement'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function show(Event $event): JsonResponse
    {
        if (!$event->isPublished() && !$this->isGranted('ROLE_ADMIN') && $event->getOrganizer() !== $this->getUser()) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        $statistics = $this->eventService->getEventStatistics($event);

        return $this->json([
            'event' => $event,
            'statistics' => $statistics
        ], Response::HTTP_OK, [], ['groups' => ['event:read', 'event:details']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        path: '/api/events',
        summary: 'Créer un événement',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'startDate', 'location', 'maxParticipants'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Conférence Tech 2024'),
                    new OA\Property(property: 'description', type: 'string', example: 'Une conférence sur les dernières technologies'),
                    new OA\Property(property: 'startDate', type: 'string', format: 'date-time', example: '2024-06-15T14:00:00Z'),
                    new OA\Property(property: 'endDate', type: 'string', format: 'date-time', example: '2024-06-15T18:00:00Z'),
                    new OA\Property(property: 'location', type: 'string', example: 'Paris, France'),
                    new OA\Property(property: 'maxParticipants', type: 'integer', example: 100),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 25.99),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), example: ['tech', 'conference'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Événement créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'event',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'Conférence Tech 2024'),
                                new OA\Property(property: 'description', type: 'string', example: 'Une conférence sur les dernières technologies'),
                                new OA\Property(property: 'startDate', type: 'string', format: 'date-time', example: '2024-06-15T14:00:00Z'),
                                new OA\Property(property: 'location', type: 'string', example: 'Paris, France'),
                                new OA\Property(property: 'maxParticipants', type: 'integer', example: 100),
                                new OA\Property(property: 'isPublished', type: 'boolean', example: false)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $event = $this->serializer->deserialize(
            $request->getContent(),
            Event::class,
            'json',
            ['groups' => ['event:write']]
        );

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $event = $this->eventService->createEvent($event, $user);

        return $this->json($event, Response::HTTP_CREATED, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Put(
        path: '/api/events/{id}',
        summary: 'Modifier un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'startDate', 'location', 'maxParticipants'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Conférence Tech 2024'),
                    new OA\Property(property: 'description', type: 'string', example: 'Une conférence sur les dernières technologies'),
                    new OA\Property(property: 'startDate', type: 'string', format: 'date-time', example: '2024-06-15T14:00:00Z'),
                    new OA\Property(property: 'endDate', type: 'string', format: 'date-time', example: '2024-06-15T18:00:00Z'),
                    new OA\Property(property: 'location', type: 'string', example: 'Paris, France'),
                    new OA\Property(property: 'maxParticipants', type: 'integer', example: 100),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 25.99),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), example: ['tech', 'conference'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Événement modifié avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'event',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'Conférence Tech 2024 - Mise à jour'),
                                new OA\Property(property: 'description', type: 'string', example: 'Une conférence sur les dernières technologies - Version mise à jour'),
                                new OA\Property(property: 'startDate', type: 'string', format: 'date-time', example: '2024-06-15T14:00:00Z'),
                                new OA\Property(property: 'location', type: 'string', example: 'Paris, France'),
                                new OA\Property(property: 'maxParticipants', type: 'integer', example: 150),
                                new OA\Property(property: 'isPublished', type: 'boolean', example: true)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function update(Request $request, Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->eventService->canUserEditEvent($user, $event)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Event::class,
            'json',
            ['object_to_populate' => $event, 'groups' => ['event:write']]
        );

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->eventService->updateEvent($event);

        return $this->json($event, Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Patch(
        path: '/api/events/{id}/publish',
        summary: 'Publier un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Événement publié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Événement non trouvé')
        ]
    )]
    public function publish(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->eventService->canUserEditEvent($user, $event)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $event = $this->eventService->publishEvent($event);

        return $this->json($event, Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Delete(
        path: '/api/events/{id}',
        summary: 'Supprimer un événement',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Événement supprimé'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Événement non trouvé'),
            new OA\Response(response: 409, description: 'Impossible de supprimer')
        ]
    )]
    public function delete(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->eventService->canUserEditEvent($user, $event)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->eventService->canEventBeDeleted($event)) {
            return $this->json(['error' => 'Impossible de supprimer cet événement'], Response::HTTP_CONFLICT);
        }

        $this->eventService->deleteEvent($event);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/popular', name: 'popular', methods: ['GET'])]
    #[OA\Get(
        path: '/api/events/popular',
        summary: 'Événements populaires',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Événements populaires')
        ]
    )]
    public function popular(Request $request): JsonResponse
    {
        $limit = min(50, max(1, $request->query->getInt('limit', 10)));
        $events = $this->eventService->getPopularEvents($limit);

        return $this->json($events, Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    #[OA\Get(
        path: '/api/events/upcoming',
        summary: 'Événements à venir',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Événements à venir')
        ]
    )]
    public function upcoming(Request $request): JsonResponse
    {
        $limit = $request->query->has('limit') ? 
            min(50, max(1, $request->query->getInt('limit'))) : 
            null;
            
        $events = $this->eventService->getUpcomingEvents($limit);

        return $this->json($events, Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }
}
