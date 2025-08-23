<?php

namespace App\Tests\Service;

use App\Service\NotificationService;
use App\Strategy\EmailNotificationStrategy;
use App\Strategy\SlackNotificationStrategy;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationServiceTest extends TestCase
{
    private NotificationService $notificationService;
    private EmailNotificationStrategy $emailStrategy;
    private SlackNotificationStrategy $slackStrategy;
    private User $user;

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->notificationService = new NotificationService($logger);
        
        $this->emailStrategy = $this->createMock(EmailNotificationStrategy::class);
        $this->emailStrategy->method('getName')->willReturn('email');
        
        $this->slackStrategy = $this->createMock(SlackNotificationStrategy::class);
        $this->slackStrategy->method('getName')->willReturn('slack');
        
        $this->notificationService->addStrategy($this->emailStrategy);
        $this->notificationService->addStrategy($this->slackStrategy);
        
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setFirstName('Test');
        $this->user->setLastName('User');
    }

    public function testServiceHasStrategies(): void
    {
        $strategies = $this->notificationService->getAvailableStrategies();
        
        $this->assertCount(2, $strategies);
        $this->assertContains('email', $strategies);
        $this->assertContains('slack', $strategies);
    }

    public function testSendWithValidStrategy(): void
    {
        $this->emailStrategy
            ->expects($this->once())
            ->method('send')
            ->with($this->user, 'Test Subject', 'Test Message', [])
            ->willReturn(true);

        $result = $this->notificationService->send('email', $this->user, 'Test Subject', 'Test Message');
        
        $this->assertTrue($result);
    }

    public function testSendWithInvalidStrategy(): void
    {
        $result = $this->notificationService->send('invalid', $this->user, 'Test Subject', 'Test Message');
        
        $this->assertFalse($result);
    }

    public function testHasStrategy(): void
    {
        $this->assertTrue($this->notificationService->hasStrategy('email'));
        $this->assertTrue($this->notificationService->hasStrategy('slack'));
        $this->assertFalse($this->notificationService->hasStrategy('sms'));
    }
}
