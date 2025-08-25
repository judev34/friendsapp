# 🔄 Workflow des EventListeners dans Symfony

## 📋 Étape par Étape : Création d'un Événement

### 1. **Requête API** 
```
POST /api/events
{
  "title": "Conférence Tech 2024",
  "description": "...",
  "startDate": "2024-06-15T14:00:00Z"
}
```

### 2. **EventController::create()** 
```php
// src/Controller/Api/EventController.php ligne 156
public function create(Request $request): JsonResponse
{
    // Désérialisation + validation
    $event = $this->serializer->deserialize(...);
    
    // 🎯 POINT CLÉ : Appel du service
    $event = $this->eventService->createEvent($event, $user);
    
    return $this->json($event, 201);
}
```

### 3. **EventService::createEvent()** 
```php
// src/Service/EventService.php ligne 29
public function createEvent(Event $event, User $organizer): Event
{
    // Logique métier
    $event->setOrganizer($organizer);
    $event->setCreatedAt(new \DateTime());
    
    // Sauvegarde en base
    $this->entityManager->persist($event);
    $this->entityManager->flush();
    
    // 🚀 MAGIE ICI : Dispatch de l'événement
    $this->eventDispatcher->dispatch(
        new EventCreatedEvent($event), 
        EventCreatedEvent::NAME
    );
    
    return $event;
}
```

### 4. **EventDispatcher (Symfony Core)**
```php
// Symfony trouve automatiquement tous les listeners
// qui écoutent "EventCreatedEvent::NAME"
```

### 5. **EventNotificationListener::onEventCreated()**
```php
// src/EventListener/EventNotificationListener.php ligne 21
#[AsEventListener(event: EventCreatedEvent::NAME)]
public function onEventCreated(EventCreatedEvent $event): void
{
    $eventEntity = $event->getEvent();
    $organizer = $eventEntity->getOrganizer();
    
    // 📧 Email à l'organisateur
    $this->notificationService->send('email', $organizer, $subject, $message);
    
    // 📱 Notification Slack aux admins
    $this->notificationService->send('slack', $organizer, $subject, $slackMessage);
}
```

## 🔧 Comment Symfony Fait la Liaison

### **1. Autodiscovery**
```yaml
# config/services.yaml
App\:
    resource: '../src/'
    # Symfony scanne automatiquement tous les fichiers
    # et trouve les classes avec #[AsEventListener]
```

### **2. Attribut #[AsEventListener]**
```php
#[AsEventListener(event: EventCreatedEvent::NAME)]
//                 ↑
//        Dit à Symfony : "Écoute cet événement"
```

### **3. EventDispatcher Registry**
```php
// Symfony maintient une registry interne :
[
    'event.created' => [
        EventNotificationListener::onEventCreated,
        // Autres listeners...
    ],
    'registration.confirmed' => [
        RegistrationNotificationListener::onRegistrationConfirmed
    ]
]
```

## ⚡ Avantages du Pattern

### **Découplage**
- `EventService` ne connaît pas `NotificationService`
- Facile d'ajouter/supprimer des listeners
- Chaque listener a une responsabilité unique

### **Extensibilité**
```php
// Ajouter facilement un nouveau listener
#[AsEventListener(event: EventCreatedEvent::NAME)]
public function onEventCreatedAnalytics(EventCreatedEvent $event): void
{
    $this->analytics->track('event_created', $event->getEvent());
}
```

### **Testabilité**
- Chaque listener peut être testé indépendamment
- Possibilité de désactiver des listeners en test

## 🎯 Ordre d'Exécution

```php
// Par défaut : ordre alphabétique des classes
// Mais on peut spécifier la priorité :
#[AsEventListener(event: EventCreatedEvent::NAME, priority: 100)]
public function highPriorityListener(): void { }

#[AsEventListener(event: EventCreatedEvent::NAME, priority: -100)]
public function lowPriorityListener(): void { }
```
