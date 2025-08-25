# 🧪 Guide Professionnel des Tests API

## 🎯 Comment Tester en Milieu Professionnel

### **1. Stratégies de Tests par Niveau d'Accès**

#### **Tests Publics (Sans Authentification)**
```php
// ✅ Endpoints accessibles à tous
class PublicApiTest extends WebTestCase
{
    public function testGetPublicEvents(): void
    {
        $this->client->request('GET', '/api/events');
        $this->assertResponseIsSuccessful();
    }
}
```

#### **Tests Authentifiés (Avec Utilisateur)**
```php
// ✅ Simulation d'utilisateur connecté
class AuthenticatedApiTest extends WebTestCase
{
    private User $testUser;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = $this->createTestUser();
    }
    
    public function testCreateEventAsUser(): void
    {
        $this->client->loginUser($this->testUser); // 🔑 Connexion simulée
        
        $this->client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($eventData));
        
        $this->assertResponseStatusCodeSame(201);
    }
}
```

#### **Tests Admin (Avec Privilèges)**
```php
// ✅ Tests avec utilisateur admin
public function testAdminStatistics(): void
{
    $admin = $this->createAdminUser();
    $this->client->loginUser($admin);
    
    $this->client->request('GET', '/api/events/statistics');
    $this->assertResponseIsSuccessful();
}
```

### **2. Bonnes Pratiques Professionnelles**

#### **Séparation des Tests par Responsabilité**
```
tests/
├── Unit/           # Tests unitaires (services, repositories)
├── Functional/     # Tests fonctionnels (endpoints)
│   ├── PublicApiTest.php      # Tests sans auth
│   ├── AuthenticatedApiTest.php # Tests avec auth
│   └── AdminApiTest.php       # Tests admin
└── Integration/    # Tests d'intégration complète
```

#### **Fixtures et Données de Test**
```php
// ✅ Création de données de test isolées
private function createTestUser(array $roles = ['ROLE_USER']): User
{
    $user = new User();
    $user->setEmail('test@example.com');
    $user->setRoles($roles);
    // Hash de mot de passe de test
    $user->setPassword('$2y$13$test.hash');
    
    $this->entityManager->persist($user);
    $this->entityManager->flush();
    
    return $user;
}
```

#### **Nettoyage Automatique**
```php
protected function tearDown(): void
{
    // ✅ Nettoyer les données après chaque test
    if ($this->testUser) {
        $this->entityManager->remove($this->testUser);
        $this->entityManager->flush();
    }
    parent::tearDown();
}
```

### **3. Environnements de Test**

#### **Base de Données de Test**
```yaml
# .env.test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
```

#### **Configuration Séparée**
```yaml
# config/packages/test/framework.yaml
framework:
    test: true
    session:
        storage_factory_id: session.storage.factory.mock_file
```

### **4. Types de Tests par Cas d'Usage**

#### **Tests de Sécurité**
```php
public function testUnauthorizedAccess(): void
{
    // ✅ Vérifier que l'accès est refusé
    $this->client->request('POST', '/api/events');
    $this->assertResponseStatusCodeSame(401);
}

public function testForbiddenAccess(): void
{
    // ✅ Utilisateur connecté mais sans droits
    $this->client->loginUser($this->regularUser);
    $this->client->request('GET', '/api/admin/statistics');
    $this->assertResponseStatusCodeSame(403);
}
```

#### **Tests de Validation**
```php
public function testInvalidData(): void
{
    $this->client->loginUser($this->testUser);
    
    $this->client->request('POST', '/api/events', [], [], [
        'CONTENT_TYPE' => 'application/json'
    ], json_encode(['title' => ''])); // Données invalides
    
    $this->assertResponseStatusCodeSame(400);
}
```

#### **Tests de Workflow**
```php
public function testCompleteEventWorkflow(): void
{
    $this->client->loginUser($this->testUser);
    
    // 1. Créer
    $response = $this->createEvent();
    $eventId = $response['id'];
    
    // 2. Modifier
    $this->updateEvent($eventId);
    
    // 3. Publier
    $this->publishEvent($eventId);
    
    // 4. Vérifier état final
    $this->assertEventIsPublished($eventId);
}
```

### **5. Outils Professionnels**

#### **PHPUnit + Symfony**
```bash
# Tests par groupe
php bin/phpunit --group=public
php bin/phpunit --group=authenticated
php bin/phpunit --group=admin

# Tests avec couverture
php bin/phpunit --coverage-html coverage/
```

#### **Fixtures avec Alice/Foundry**
```php
// ✅ Génération de données de test
$user = UserFactory::createOne(['email' => 'test@example.com']);
$events = EventFactory::createMany(10, ['organizer' => $user]);
```

#### **Tests API avec Bruno/Postman**
```json
{
  "name": "Login",
  "request": {
    "method": "POST",
    "url": "{{baseUrl}}/api/login",
    "body": {
      "email": "test@example.com",
      "password": "password"
    }
  },
  "tests": [
    "pm.test('Login successful', () => {",
    "  pm.response.to.have.status(200);",
    "});"
  ]
}
```

### **6. CI/CD et Automatisation**

#### **Pipeline de Tests**
```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php bin/phpunit
```

### **7. Stratégies par Équipe**

#### **Tests en Parallèle**
- **Frontend** : Tests E2E avec Cypress/Playwright
- **Backend** : Tests unitaires et fonctionnels PHPUnit
- **QA** : Tests manuels et automatisés
- **DevOps** : Tests d'infrastructure et performance

#### **Environnements Multiples**
- **Local** : Tests rapides pendant développement
- **CI** : Tests complets sur chaque commit
- **Staging** : Tests d'intégration avec données réelles
- **Production** : Monitoring et tests de santé

## 🎯 Réponse à Votre Situation

Pour votre API Events, la solution professionnelle est :

1. **Séparer les tests** : Publics vs Authentifiés
2. **Utiliser `loginUser()`** pour simuler l'authentification
3. **Créer des fixtures** pour les données de test
4. **Nettoyer automatiquement** après chaque test
5. **Tester tous les cas** : succès, erreurs, permissions

C'est exactement ce que nous allons implémenter maintenant !
