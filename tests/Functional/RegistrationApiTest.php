<?php

namespace App\Tests\Functional;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\RegistrationStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegistrationApiTest extends WebTestCase
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
            // Best-effort cleanup of created test data by removing Events then Users to avoid FK issues
            // We cannot easily track all entities created, so rely on DB resets between test runs if configured.
            // Here we just clear the EntityManager.
            $this->em->clear();
        }
        parent::tearDown();
    }

    private function createUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail(sprintf('user_%s@test.com', uniqid()));
        // Password not used because we authenticate with loginUser()
        $user->setPassword('$2y$13$test.password.hash');
        $user->setRoles($roles);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function createEvent(User $organizer, bool $published = true, ?int $capacity = 50): Event
    {
        $event = new Event();
        $event->setTitle('Test Event '.uniqid());
        $event->setDescription('Description de test suffisante');
        $event->setStartDate(new \DateTime('+1 week 14:00:00'));
        $event->setEndDate(new \DateTime('+1 week 18:00:00'));
        $event->setLocation('Paris, France');
        $event->setMaxParticipants($capacity);
        $event->setIsPublished($published);
        $event->setOrganizer($organizer);
        $this->em->persist($event);
        $this->em->flush();
        return $event;
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user, 'api');
    }

    public function testListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/registrations');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpcomingRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/registrations/upcoming');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegisterCreatesRegistration(): void
    {
        $user = $this->createUser();
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 100);

        $this->login($user);
        $this->client->request('POST', '/api/registrations/events/'.$event->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('event', $data);
        $this->assertEquals($event->getId(), $data['event']['id']);
    }

    public function testRegisterOnUnknownEventReturns404(): void
    {
        $user = $this->createUser();
        $this->login($user);
        $this->client->request('POST', '/api/registrations/events/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testOrganizerCannotRegisterToOwnEvent(): void
    {
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 10);
        $this->login($organizer);
        $this->client->request('POST', '/api/registrations/events/'.$event->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testConfirmRegistrationAsOwner(): void
    {
        $user = $this->createUser();
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 10);

        // Create registration via simple POST first
        $this->login($user);
        $this->client->request('POST', '/api/registrations/events/'.$event->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = json_decode($this->client->getResponse()->getContent(), true);

        // Confirm
        $this->client->request('PATCH', '/api/registrations/'.$created['id'].'/confirm');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(RegistrationStatus::CONFIRMED->value, $data['status']);
    }

    public function testCancelRegistrationAsOwner(): void
    {
        $user = $this->createUser();
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 10);

        $this->login($user);
        $this->client->request('POST', '/api/registrations/events/'.$event->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('PATCH', '/api/registrations/'.$created['id'].'/cancel');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(RegistrationStatus::CANCELLED->value, $data['status']);
    }

    public function testCancelRegistrationNotOwnerReturnsBadRequest(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 10);

        // Create registration for owner
        $this->login($owner);
        $this->client->request('POST', '/api/registrations/events/'.$event->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = json_decode($this->client->getResponse()->getContent(), true);

        // Try cancel as other user
        $this->login($other);
        $this->client->request('PATCH', '/api/registrations/'.$created['id'].'/cancel');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testShowRegistrationAccessRules(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 10);

        // Create registration for owner
        $this->login($owner);
        $this->client->request('POST', '/api/registrations/events/'.$event->getId());
        $created = json_decode($this->client->getResponse()->getContent(), true);

        // Access as stranger (not owner, not organizer)
        $this->login($stranger);
        $this->client->request('GET', '/api/registrations/'.$created['id']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Access as organizer
        $this->login($organizer);
        $this->client->request('GET', '/api/registrations/'.$created['id']);
        $this->assertResponseIsSuccessful();
    }

    public function testFindByTicketCode(): void
    {
        $user = $this->createUser();
        $organizer = $this->createUser();
        $event = $this->createEvent($organizer, published: true, capacity: 10);

        // Create registration entity directly to get ticketCode
        $reg = new Registration();
        $reg->setUser($user)->setEvent($event);
        // Keep default PENDING status and auto-generated ticketCode
        $this->em->persist($reg);
        $this->em->flush();

        $this->client->request('GET', '/api/registrations/ticket/'.$reg->getTicketCode());
        $this->assertResponseIsSuccessful();
        $found = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($reg->getId(), $found['id']);

        $this->client->request('GET', '/api/registrations/ticket/INVALIDCODE');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
