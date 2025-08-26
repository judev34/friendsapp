<?php

namespace App\Tests\Functional;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UsersApiTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        if ($this->em && $this->em->isOpen()) {
            $this->em->clear();
        }
        parent::tearDown();
    }

    private function createUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail(sprintf('user_%s@test.com', uniqid()));
        $user->setPassword('$2y$13$test.password.hash');
        $user->setRoles($roles);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function createEventFor(User $organizer): Event
    {
        $event = new Event();
        $event->setTitle('Event '.uniqid());
        $event->setDescription('Description pour tests utilisateurs');
        $event->setStartDate(new \DateTime('+10 days 10:00:00'));
        $event->setEndDate(new \DateTime('+10 days 12:00:00'));
        $event->setLocation('Marseille');
        $event->setMaxParticipants(30);
        $event->setIsPublished(true);
        $event->setOrganizer($organizer);
        $this->em->persist($event);
        $this->em->flush();
        return $event;
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user, 'api');
    }

    public function testOrganizersRequiresAuth(): void
    {
        // Without auth
        $this->client->request('GET', '/api/users/organizers');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // With auth
        $user = $this->createUser();
        $this->login($user);
        $this->client->request('GET', '/api/users/organizers');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testProfileAndMyEvents(): void
    {
        $user = $this->createUser();
        $this->createEventFor($user);

        // Unauthorized
        $this->client->request('GET', '/api/users/me');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Authorized
        $this->login($user);
        $this->client->request('GET', '/api/users/me');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/users/me/events');
        $this->assertResponseIsSuccessful();
        $events = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($events);
    }

    public function testSearchAndStatsRequireAdmin(): void
    {
        $user = $this->createUser();
        $admin = $this->createUser(['ROLE_ADMIN']);

        // User forbidden
        $this->login($user);
        $this->client->request('GET', '/api/users/search', ['q' => 'test']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request('GET', '/api/users/stats');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Admin allowed
        $this->login($admin);
        $this->client->request('GET', '/api/users/search', ['q' => 'test']);
        $this->assertResponseIsSuccessful();
        $res = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($res);

        $this->client->request('GET', '/api/users/stats');
        $this->assertResponseIsSuccessful();
        $stats = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('registration_stats', $stats);
        $this->assertArrayHasKey('most_active_users', $stats);
    }
}
