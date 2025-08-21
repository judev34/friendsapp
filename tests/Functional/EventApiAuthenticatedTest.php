<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EventApiAuthenticatedTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;
    private $adminUser;

    protected function setUp(): void
    {
        // Reset kernel between tests to avoid multiple boot issues
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);
        
        // Créer des utilisateurs de test avec des emails uniques
        $uniqueId = uniqid();
        $this->testUser = $this->createTestUser("user{$uniqueId}@test.com", ['ROLE_USER']);
        $this->adminUser = $this->createTestUser("admin{$uniqueId}@test.com", ['ROLE_ADMIN']);
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
        if ($this->entityManager && $this->entityManager->isOpen()) {
            // Supprimer d'abord les événements liés aux utilisateurs de test pour éviter les contraintes FK
            if ($this->testUser) {
                $events = $this->entityManager->getRepository(Event::class)->findBy(['organizer' => $this->testUser]);
                foreach ($events as $event) {
                    $this->entityManager->remove($event);
                }
            }
            if ($this->adminUser) {
                $events = $this->entityManager->getRepository(Event::class)->findBy(['organizer' => $this->adminUser]);
                foreach ($events as $event) {
                    $this->entityManager->remove($event);
                }
            }

            // Réattacher les entités User détachées avant suppression
            if ($this->testUser) {
                $managedTestUser = $this->entityManager->contains($this->testUser)
                    ? $this->testUser
                    : $this->entityManager->getRepository(User::class)->find($this->testUser->getId());
                if ($managedTestUser) {
                    $this->entityManager->remove($managedTestUser);
                }
            }
            if ($this->adminUser) {
                $managedAdminUser = $this->entityManager->contains($this->adminUser)
                    ? $this->adminUser
                    : $this->entityManager->getRepository(User::class)->find($this->adminUser->getId());
                if ($managedAdminUser) {
                    $this->entityManager->remove($managedAdminUser);
                }
            }

            $this->entityManager->flush();
        }
        
        parent::tearDown();
    }

    private function createTestUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('$2y$13$test.password.hash'); // Hash de test
        $user->setRoles($roles);
        $user->setFirstName('Test');
        $user->setLastName('User');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function loginUser(User $user): void
    {
        // Authenticate on the 'api' firewall (routes under /api use this firewall)
        $this->client->loginUser($user, 'api');
    }

    public function testCreateEventAsAuthenticatedUser(): void
    {
        $this->loginUser($this->testUser);
        
        $eventData = [
            'title' => 'Test Event Authenticated',
            'description' => 'Description de test',
            'startDate' => (new \DateTime('+1 week'))->format('c'),
            'endDate' => (new \DateTime('+1 week +4 hours'))->format('c'),
            'location' => 'Paris, France',
            'maxParticipants' => 50,
            'price' => '25.00',
            'tags' => ['tech', 'meetup']
        ];

        $this->client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($eventData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($eventData['title'], $data['title']);
        $this->assertEquals($eventData['description'], $data['description']);
        $this->assertEquals($this->testUser->getId(), $data['organizer']['id']);
    }

    public function testGetRecommendedEventsAsAuthenticatedUser(): void
    {
        $this->loginUser($this->testUser);
        
        $this->client->request('GET', '/api/events/recommended', ['limit' => 5]);
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(5, count($data));
    }

    public function testDuplicateEventAsOwner(): void
    {
        $this->loginUser($this->testUser);
        
        // Créer d'abord un événement
        $event = new Event();
        $event->setTitle('Original Event');
        $event->setDescription('Original Description');
        $event->setStartDate(new \DateTime('+1 week'));
        $event->setEndDate(new \DateTime('+1 week +4 hours'));
        $event->setLocation('Paris');
        $event->setMaxParticipants(50);
        $event->setPrice('25.00');
        $event->setOrganizer($this->testUser);
        
        $this->entityManager->persist($event);
        $this->entityManager->flush();
        
        // Dupliquer l'événement
        $this->client->request('POST', '/api/events/' . $event->getId() . '/duplicate');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Copie', $data['title']);
        $this->assertEquals($event->getDescription(), $data['description']);
        $this->assertEquals($this->testUser->getId(), $data['organizer']['id']);
    }

    public function testGetGlobalStatisticsAsAdmin(): void
    {
        $this->loginUser($this->adminUser);
        
        $this->client->request('GET', '/api/events/statistics');
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('totalEvents', $data);
        $this->assertArrayHasKey('totalRegistrations', $data);
        $this->assertArrayHasKey('totalRevenue', $data);
        $this->assertArrayHasKey('eventsThisMonth', $data);
        $this->assertArrayHasKey('averageParticipants', $data);
        $this->assertArrayHasKey('popularCategories', $data);
    }

    public function testGetGlobalStatisticsAsUserForbidden(): void
    {
        $this->loginUser($this->testUser);
        
        $this->client->request('GET', '/api/events/statistics');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateEventAsOwner(): void
    {
        $this->loginUser($this->testUser);
        
        // Créer un événement
        $event = new Event();
        $event->setTitle('Event to Update');
        $event->setDescription('Original Description');
        $event->setStartDate(new \DateTime('+2 weeks 14:00:00'));
        $event->setEndDate(new \DateTime('+2 weeks 18:00:00'));
        $event->setLocation('Paris');
        $event->setMaxParticipants(50);
        $event->setOrganizer($this->testUser);
        
        $this->entityManager->persist($event);
        $this->entityManager->flush();
        
        // Mettre à jour l'événement
        $updateData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated Description',
            'maxParticipants' => 100
        ];
        
        $this->client->request('PUT', '/api/events/' . $event->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($updateData));
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($updateData['title'], $data['title']);
        $this->assertEquals($updateData['description'], $data['description']);
        $this->assertEquals($updateData['maxParticipants'], $data['maxParticipants']);
    }

    public function testDeleteEventAsOwner(): void
    {
        $this->loginUser($this->testUser);
        
        // Créer un événement
        $event = new Event();
        $event->setTitle('Event to Delete');
        $event->setDescription('Description');
        $event->setStartDate(new \DateTime('+3 weeks 14:00:00'));
        $event->setEndDate(new \DateTime('+3 weeks 18:00:00'));
        $event->setLocation('Paris');
        $event->setMaxParticipants(50);
        $event->setOrganizer($this->testUser);
        
        $this->entityManager->persist($event);
        $this->entityManager->flush();
        
        $eventId = $event->getId();
        
        // Supprimer l'événement
        $this->client->request('DELETE', '/api/events/' . $eventId);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        
        // Vérifier que l'événement n'existe plus
        $this->client->request('GET', '/api/events/' . $eventId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
