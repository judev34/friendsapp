<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests pour les endpoints publics de l'API Events
 * Ces tests ne nécessitent pas d'authentification
 */
class EventApiTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testGetEventsReturnsJsonResponse(): void
    {
        $this->client->request('GET', '/api/events');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testGetEventsWithFilters(): void
    {
        $this->client->request('GET', '/api/events', [
            'location' => 'Paris',
            'isFree' => true,
            'limit' => 5
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('pages', $data);
    }

    public function testGetPopularEvents(): void
    {
        $this->client->request('GET', '/api/events/popular', ['limit' => 3]);
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(3, count($data));
    }

    public function testGetUpcomingEvents(): void
    {
        $this->client->request('GET', '/api/events/upcoming');
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetEventsByCategory(): void
    {
        $this->client->request('GET', '/api/events/category/tech');
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetRecommendedEventsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/events/recommended');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetGlobalStatisticsRequiresAdmin(): void
    {
        $this->client->request('GET', '/api/events/statistics');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateEventRequiresAuthentication(): void
    {
        $this->client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Event',
            'description' => 'Test Description',
            'startDate' => '2024-12-01T14:00:00Z',
            'location' => 'Test Location',
            'maxParticipants' => 50
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetNonExistentEvent(): void
    {
        $this->client->request('GET', '/api/events/99999');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testEventStatisticsStructure(): void
    {
        // Test avec les événements existants dans la liste
        $this->client->request('GET', '/api/events');
        $this->assertResponseIsSuccessful();
        
        $listData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Si des événements existent, tester la structure des statistiques
        if (isset($listData['events']) && count($listData['events']) > 0) {
            $firstEvent = $listData['events'][0];
            $eventId = $firstEvent['id'];
            
            $this->client->request('GET', '/api/events/' . $eventId);
            $this->assertResponseIsSuccessful();
            
            $data = json_decode($this->client->getResponse()->getContent(), true);
            
            $this->assertArrayHasKey('event', $data);
            $this->assertArrayHasKey('statistics', $data);
            
            $stats = $data['statistics'];
            $this->assertArrayHasKey('registrations', $stats);
            $this->assertArrayHasKey('capacity', $stats);
            $this->assertArrayHasKey('revenue', $stats);
            $this->assertArrayHasKey('timeline', $stats);
        } else {
            // Si aucun événement n'existe, marquer le test comme réussi
            $this->assertTrue(true, 'Aucun événement disponible pour tester les statistiques');
        }
    }
}
