<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\RegistrationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EventApiIntegrationTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;
    private $testEvent;

    protected function setUp(): void
    {
        if (!$this->client) {
            $this->client = static::createClient();
            $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);
        }
        
        // Créer des données de test
        $this->testUser = $this->createTestUser();
        $this->testEvent = $this->createTestEvent();
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
        if ($this->entityManager && $this->entityManager->isOpen()) {
            $this->cleanupTestData();
        }
        parent::tearDown();
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('integration' . uniqid() . '@test.com');
        // Hash du mot de passe 'test123' pour les tests
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        $user->setPassword($passwordHasher->hashPassword($user, 'test123'));
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Integration');
        $user->setLastName('Test');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createTestEvent(): Event
    {
        $event = new Event();
        $event->setTitle('Integration Test Event');
        $event->setDescription('Event for integration testing');
        $event->setStartDate(new \DateTime('+1 week'));
        $event->setEndDate(new \DateTime('+1 week +4 hours'));
        $event->setLocation('Test Location');
        $event->setMaxParticipants(100);
        $event->setPrice('15.00');
        $event->setOrganizer($this->testUser);
        $event->setTags(['integration', 'test']);
        
        $this->entityManager->persist($event);
        $this->entityManager->flush();
        
        return $event;
    }

    private function cleanupTestData(): void
    {
        try {
            // Supprimer les inscriptions liées
            if ($this->testEvent) {
                $registrations = $this->entityManager->getRepository(Registration::class)
                    ->findBy(['event' => $this->testEvent]);
                
                foreach ($registrations as $registration) {
                    $this->entityManager->remove($registration);
                }
            }
            
            if ($this->testEvent) {
                $this->entityManager->remove($this->testEvent);
            }
            if ($this->testUser) {
                $this->entityManager->remove($this->testUser);
            }
            
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Ignorer les erreurs de nettoyage
        }
    }

    public function testCompleteEventWorkflow(): void
    {
        // Authentification via login API
        $this->authenticateUser();
        
        // 1. Créer un événement
        $eventData = [
            'title' => 'Workflow Test Event',
            'description' => 'Complete workflow test',
            'startDate' => (new \DateTime('+2 weeks'))->format('c'),
            'endDate' => (new \DateTime('+2 weeks +3 hours'))->format('c'),
            'location' => 'Workflow Location',
            'maxParticipants' => 50,
            'price' => '20.00',
            'tags' => ['workflow', 'test']
        ];

        $this->client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($eventData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseContent = $this->client->getResponse()->getContent();
        $createdEvent = json_decode($responseContent, true);
        
        // Debug: vérifier si la réponse contient les données attendues
        $this->assertNotNull($createdEvent, 'La réponse de création d\'événement ne doit pas être null');
        $this->assertArrayHasKey('id', $createdEvent, 'La réponse doit contenir un ID');
        $this->assertArrayHasKey('title', $createdEvent, 'La réponse doit contenir un titre');
        
        $eventId = $createdEvent['id'];

        // 2. Récupérer l'événement créé
        $this->client->request('GET', '/api/events/' . $eventId);
        $this->assertResponseIsSuccessful();
        
        $getResponseContent = $this->client->getResponse()->getContent();
        $retrievedEvent = json_decode($getResponseContent, true);
        $this->assertEquals($eventData['title'], $retrievedEvent['event']['title']);

        // 3. Mettre à jour l'événement
        $updateData = [
            'title' => 'Updated Workflow Event',
            'maxParticipants' => 75
        ];

        $this->client->request('PUT', '/api/events/' . $eventId, [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($updateData));

        $this->assertResponseIsSuccessful();
        $updatedEvent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($updateData['title'], $updatedEvent['title']);
        $this->assertEquals($updateData['maxParticipants'], $updatedEvent['maxParticipants']);

        // 4. Publier l'événement
        $this->client->request('PATCH', '/api/events/' . $eventId . '/publish');
        $this->assertResponseIsSuccessful();
        
        $publishedEvent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($publishedEvent['isPublished']);

        // 5. Dupliquer l'événement
        $this->client->request('POST', '/api/events/' . $eventId . '/duplicate');
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $duplicatedEvent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('(Copie)', $duplicatedEvent['title']);
        $duplicatedEventId = $duplicatedEvent['id'];

        // 6. Supprimer l'événement dupliqué
        $this->client->request('DELETE', '/api/events/' . $duplicatedEventId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 7. Vérifier que l'événement dupliqué n'existe plus
        $this->client->request('GET', '/api/events/' . $duplicatedEventId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // 8. Nettoyer - supprimer l'événement original
        $this->client->request('DELETE', '/api/events/' . $eventId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testEventStatisticsWithRealData(): void
    {
        // Authentification via login API
        $this->authenticateUser();
        
        // Créer une inscription pour avoir des statistiques réelles
        $registration = new Registration();
        $registration->setEvent($this->testEvent);
        $registration->setUser($this->testUser);
        $registration->setStatus(RegistrationStatus::CONFIRMED);
        $registration->setRegisteredAt(new \DateTime());
        
        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        // Récupérer les statistiques de l'événement
        $this->client->request('GET', '/api/events/' . $this->testEvent->getId());
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        // Vérifier la structure des statistiques
        if (isset($data['statistics'])) {
            $stats = $data['statistics'];
            
            $this->assertArrayHasKey('registrations', $stats);
            $this->assertArrayHasKey('capacity', $stats);
            $this->assertArrayHasKey('revenue', $stats);
            $this->assertArrayHasKey('timeline', $stats);
            
            // Vérifier les données de capacité
            $this->assertGreaterThanOrEqual(0, $stats['capacity']['occupancyRate']);
            $this->assertLessThanOrEqual($this->testEvent->getMaxParticipants(), $stats['capacity']['availableSpots']);
            
            // Vérifier les revenus
            $this->assertGreaterThanOrEqual(0, $stats['revenue']['total']);
            $this->assertGreaterThanOrEqual(0, $stats['revenue']['averagePerParticipant']);
        }
    }

    public function testSearchAndFilteringIntegration(): void
    {
        // Test de recherche avec différents filtres
        $filters = [
            ['location' => 'Test Location'],
            ['isFree' => false],
            ['tags' => ['integration']],
            ['startDate' => (new \DateTime())->format('Y-m-d')],
            ['limit' => 10, 'page' => 1]
        ];

        foreach ($filters as $filter) {
            $this->client->request('GET', '/api/events', $filter);
            $this->assertResponseIsSuccessful();
            
            $data = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('events', $data);
            $this->assertArrayHasKey('total', $data);
            $this->assertArrayHasKey('page', $data);
            $this->assertArrayHasKey('limit', $data);
            $this->assertArrayHasKey('pages', $data);
        }
    }

    public function testEventsByCategory(): void
    {
        // Tester la récupération d'événements par catégorie
        $this->client->request('GET', '/api/events/category/integration');
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        
        // Vérifier que tous les événements retournés ont le bon tag
        foreach ($data as $event) {
            $this->assertContains('integration', $event['tags']);
        }
    }

    public function testPopularEventsWithRealData(): void
    {
        // Créer des utilisateurs supplémentaires et des inscriptions pour rendre l'événement populaire
        for ($i = 0; $i < 3; $i++) {
            $user = new User();
            $user->setEmail('popular' . $i . uniqid() . '@test.com');
            $user->setPassword('$2y$13$test.password.hash');
            $user->setRoles(['ROLE_USER']);
            $user->setFirstName('Popular' . $i);
            $user->setLastName('User');
            
            $this->entityManager->persist($user);
            
            $registration = new Registration();
            $registration->setEvent($this->testEvent);
            $registration->setUser($user);
            $registration->setStatus(RegistrationStatus::CONFIRMED);
            $registration->setRegisteredAt(new \DateTime());
            
            $this->entityManager->persist($registration);
        }
        $this->entityManager->flush();

        $this->client->request('GET', '/api/events/popular', ['limit' => 5]);
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(5, count($data));
    }

    private function authenticateUser(): void
    {
        // Connexion via l'API de login
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => $this->testUser->getEmail(),
            'password' => 'test123' // Mot de passe en clair pour les tests
        ]));
        
        $this->assertResponseIsSuccessful();
    }
}
