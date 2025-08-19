<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api/users', name: 'api_users_')]
#[OA\Tag(name: 'Users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    #[Route('/me', name: 'profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/users/me',
        summary: 'Mon profil',
        responses: [
            new OA\Response(response: 200, description: 'Profil utilisateur'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read', 'user:profile']]);
    }

    #[Route('/me/events', name: 'my_events', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/users/me/events',
        summary: 'Mes événements organisés',
        responses: [
            new OA\Response(response: 200, description: 'Événements organisés'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function myEvents(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getOrganizedEvents(), Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/organizers', name: 'organizers', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/organizers',
        summary: 'Liste des organisateurs',
        responses: [
            new OA\Response(response: 200, description: 'Liste des organisateurs')
        ]
    )]
    public function organizers(): JsonResponse
    {
        $organizers = $this->userRepository->findOrganizers();

        return $this->json($organizers, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/users/search',
        summary: 'Rechercher des utilisateurs',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Résultats de recherche'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        if (!$query) {
            return $this->json(['error' => 'Paramètre de recherche requis'], Response::HTTP_BAD_REQUEST);
        }

        $users = $this->userRepository->searchByNameOrEmail($query);

        return $this->json($users, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/users/stats',
        summary: 'Statistiques des utilisateurs',
        responses: [
            new OA\Response(response: 200, description: 'Statistiques'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function stats(): JsonResponse
    {
        $registrationStats = $this->userRepository->getUserRegistrationStats();
        $mostActive = $this->userRepository->findMostActiveUsers(10);

        return $this->json([
            'registration_stats' => $registrationStats,
            'most_active_users' => $mostActive
        ], Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }
}
