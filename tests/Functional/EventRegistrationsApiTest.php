<?php

namespace App\Tests\Functional;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\RegistrationStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EventRegistrationsApiTest extends WebTestCase
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

    private function createEvent(User $organizer, bool $published = true, ?int $capacity = 10): Event
    {
        $event = new Event();
        $event->setTitle('Event '.uniqid());
        $event->setDescription('Description suffisante pour validation');
        $event->setStartDate(new \DateTime('+2 weeks 10:00:00'));
        $event->setEndDate(new \DateTime('+2 weeks 12:00:00'));
        $event->setLocation('Lyon');
        $event->setMaxParticipants($capacity);
        $event->setIsPublished($published);
        $event->setOrganizer($organizer);
        $this->em->persist($event);
        $this->em->flush();
        return $event;
    }

    private function addRegistration(User $user, Event $event, RegistrationStatus $status): Registration
    {
        $reg = new Registration();
        $reg->setUser($user)->setEvent($event)->setStatus($status);
        $this->em->persist($reg);
        $this->em->flush();
        return $reg;
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user, 'api');
    }

    public function testListRegistrationsRequiresOrganizerOrAdmin(): void
    {
        $organizer = $this->createUser();
        $stranger = $this->createUser();
        $event = $this->createEvent($organizer);

        $participant = $this->createUser();
        $this->addRegistration($participant, $event, RegistrationStatus::PENDING);

        // Stranger cannot access
        $this->login($stranger);
        $this->client->request('GET', '/api/events/'.$event->getId().'/registrations');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Organizer can access
        $this->login($organizer);
        $this->client->request('GET', '/api/events/'.$event->getId().'/registrations');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('confirmed', $data);
        $this->assertArrayHasKey('waitlist', $data);
    }

    public function testConfirmedAndWaitlistEndpointsAccess(): void
    {
        $organizer = $this->createUser();
        $admin = $this->createUser(['ROLE_ADMIN']);
        $event = $this->createEvent($organizer);

        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $this->addRegistration($user1, $event, RegistrationStatus::CONFIRMED);
        $this->addRegistration($user2, $event, RegistrationStatus::WAITLIST);

        // Organizer - confirmed
        $this->login($organizer);
        $this->client->request('GET', '/api/events/'.$event->getId().'/registrations/confirmed');
        $this->assertResponseIsSuccessful();
        $confirmed = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($confirmed);

        // Admin - waitlist
        $this->login($admin);
        $this->client->request('GET', '/api/events/'.$event->getId().'/registrations/waitlist');
        $this->assertResponseIsSuccessful();
        $waitlist = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($waitlist);
    }

    public function testUnknownEventReturns404(): void
    {
        $user = $this->createUser();
        $this->login($user);
        $this->client->request('GET', '/api/events/999999/registrations');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
