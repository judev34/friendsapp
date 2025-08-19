<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'Authentication', description: 'Gestion de l\'authentification des utilisateurs')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstName', 'lastName'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'motdepasse123'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                                new OA\Property(property: 'lastName', type: 'string', example: 'Dupont')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 409, description: 'Email déjà utilisé')
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Données JSON invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Validation des données requises
        if (empty($data['email']) || empty($data['password']) || empty($data['firstName']) || empty($data['lastName'])) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        // Validation de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Format d\'email invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Validation du mot de passe (minimum 8 caractères)
        if (strlen($data['password']) < 8) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return new JsonResponse(['error' => 'Un utilisateur avec cet email existe déjà'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail(trim(strtolower($data['email'])));
        $user->setFirstName(trim($data['firstName']));
        $user->setLastName(trim($data['lastName']));
        $user->setRoles(['ROLE_USER']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Validation finale avec Symfony Validator
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création de l\'utilisateur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès',
            'user' => json_decode($this->serializer->serialize($user, 'json', ['groups' => ['user:read']]))
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Connexion utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                                new OA\Property(property: 'lastName', type: 'string', example: 'Dupont')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Identifiants incorrects')
        ]
    )]
    public function login(): JsonResponse
    {
        // Cette route est gérée par le système de sécurité Symfony
        // Elle retourne les informations de l'utilisateur connecté
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Échec de l\'authentification'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'message' => 'Connexion réussie',
            'user' => json_decode($this->serializer->serialize($user, 'json', ['groups' => ['user:read']]))
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Récupérer le profil utilisateur',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil utilisateur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                                new OA\Property(property: 'lastName', type: 'string', example: 'Dupont')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => json_decode($this->serializer->serialize($user, 'json', ['groups' => ['user:profile']]))
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/logout',
        summary: 'Déconnexion utilisateur',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Déconnexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function logout(): JsonResponse
    {
        // Déconnexion simple - la session sera invalidée
        return new JsonResponse([
            'message' => 'Déconnexion réussie'
        ]);
    }
}
