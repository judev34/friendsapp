<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthApiTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testRegisterValidationErrors(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['email' => 'invalid', 'password' => 'short']));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterSuccessThenLoginAndMe(): void
    {
        $email = sprintf('api_user_%s@test.com', uniqid());
        $payload = [
            'email' => $email,
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Register
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($payload));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Login
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['email' => $email, 'password' => 'password123']));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Connexion réussie', $data['message']);

        // Me
        $this->client->request('GET', '/api/me');
        $this->assertResponseIsSuccessful();
        $me = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $me);
        $this->assertEquals($email, $me['user']['email']);
    }

    public function testDuplicateRegisterReturnsConflict(): void
    {
        $email = sprintf('dup_user_%s@test.com', uniqid());
        $payload = [
            'email' => $email,
            'password' => 'password123',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ];

        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($payload));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Register again with same email
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($payload));
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testMeRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogout(): void
    {
        // Unauthenticated should be rejected by access_control
        $this->client->request('POST', '/api/logout');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
