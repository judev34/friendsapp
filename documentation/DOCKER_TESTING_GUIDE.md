# Guide des Tests en Environnement Docker

## 🎯 Objectifs

Ce guide explique comment exécuter et gérer les tests dans l'environnement Docker de FriendsApp, en respectant les bonnes pratiques de développement.

## 🏗️ Architecture des Tests

### Services de Test

Le `compose.yaml` inclut des services dédiés aux tests :

- **database-test** : Base MySQL séparée pour les tests (exposé 3307, interne 3306)
- **php-test** : Container PHP dédié aux tests
- **Profil test** : Services spécifiques activés avec `--profile test`

### Isolation des Environnements

```bash
# Environnement de développement
docker compose up -d

# Environnement de test (avec base séparée)
docker compose --profile test up -d
```

## 🚀 Exécution des Tests

### 1. Tests Unitaires et Fonctionnels

```bash
# Démarrage de l'environnement de test
docker compose --profile test up -d

# Exécution de tous les tests
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'

# Tests spécifiques
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php -d variables_order=EGPCS vendor/bin/phpunit tests/Functional/EventApiTest.php'
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php -d variables_order=EGPCS vendor/bin/phpunit --group integration'
```

### 2. Tests avec Base de Données

```bash
# Création de la base de test
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php bin/console doctrine:database:create --env=test'

# Exécution des migrations de test
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php bin/console doctrine:migrations:migrate --env=test --no-interaction'

# Chargement des fixtures (si configurées)
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php bin/console doctrine:fixtures:load --env=test --no-interaction'
```

### 3. Tests de Performance avec RabbitMQ

```bash
# Vérification du consumer RabbitMQ
docker compose --profile test exec php-test php bin/console messenger:consume async --time-limit=60

# Tests de charge des messages
docker compose --profile test exec php-test php bin/console messenger:stats
```

## 🔧 Configuration des Tests

### Variables d'Environnement

Le fichier `.env.test` doit pointer vers la base de test Docker :

```env
## À l'intérieur des conteneurs (recommandé)
DATABASE_URL=mysql://app:password@database-test:3306/friendsapp_test

## Depuis l'hôte (si vous lancez les tests hors Docker)
# DATABASE_URL=mysql://app:password@127.0.0.1:3307/friendsapp_test
MESSENGER_TRANSPORT_DSN=amqp://admin:password123@rabbitmq:5672/%2f/messages
```

### PHPUnit Configuration

Adaptation du `phpunit.dist.xml` pour Docker :

```xml
<phpunit>
    <php>
        <env name="KERNEL_CLASS" value="App\Kernel" />
        <env name="APP_ENV" value="test" />
        <env name="APP_DEBUG" value="1" />
        <env name="DATABASE_URL" value="mysql://app:password@database-test:3306/friendsapp_test" />
    </php>
</phpunit>
```

## 📊 Bonnes Pratiques

### 1. Isolation des Données

- **Base séparée** : Utilisation de `database_test` sur port 3307
- **Transactions** : Rollback automatique après chaque test
- **Fixtures** : Données de test reproductibles

### 2. Performance

```bash
# Tests en parallèle (si paratest installé)
docker compose --profile test exec php-test vendor/bin/paratest

# Cache des tests
docker compose --profile test exec php-test php bin/console cache:clear --env=test
```

### 3. Debugging

```bash
# Logs des tests
docker compose logs php-test

# Debug d'un test spécifique
docker compose --profile test exec php-test php -d variables_order=EGPCS vendor/bin/phpunit --debug tests/Functional/EventApiTest.php

# Accès au container pour debugging
docker compose exec php-test bash
```

### 4. Tests d'Intégration

```bash
# Test complet de la stack
docker compose --profile test exec php-test php bin/console debug:router
docker compose --profile test exec php-test php bin/console debug:container
docker compose --profile test exec php-test php bin/console messenger:stats
```

## 🔄 Workflow de Test Recommandé

### 1. Développement Local

```bash
# 1. Démarrage de l'environnement de test
./docker-start.sh  # ou docker-start.bat sur Windows
docker compose --profile test up -d

# 2. Préparation de la base de test
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php bin/console doctrine:database:create --env=test && \
 APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php bin/console doctrine:migrations:migrate --env=test --no-interaction'

# 3. Exécution des tests
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'

# 4. Nettoyage
docker compose --profile test down
```

### 2. Intégration Continue (CI/CD)

```yaml
# Exemple GitHub Actions
steps:
  - name: Start Docker environment
    run: docker compose --profile test up -d
  
  - name: Setup test database
    run: |
      docker compose --profile test exec -T php-test sh -lc 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" php bin/console doctrine:database:create --env=test'
      docker compose --profile test exec -T php-test sh -lc 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" php bin/console doctrine:migrations:migrate --env=test --no-interaction'
  
  - name: Run tests
    run: docker compose --profile test exec -T php-test sh -lc 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml --coverage-clover coverage.xml'
```

## 🐛 Dépannage

### Problèmes Courants

1. **Base de données non accessible**
   ```bash
   # Vérifier le statut des services
   docker compose ps
   
   # Vérifier les logs
   docker compose logs database-test
   ```

2. **RabbitMQ non disponible**
   ```bash
   # Redémarrer RabbitMQ
   docker compose restart rabbitmq
   
   # Vérifier la connexion
   docker compose --profile test exec php-test php bin/console messenger:stats
   ```

3. **Permissions de fichiers**
   ```bash
   # Corriger les permissions
   docker compose exec php-test chown -R www-data:www-data var/
   ```

### Commandes de Diagnostic

```bash
# État des services
docker compose ps

# Utilisation des ressources
docker stats

# Logs en temps réel
docker compose logs -f php-test

# Inspection d'un container
docker compose exec php-test php -m  # Extensions PHP
docker compose exec php-test php -v  # Version PHP
```

## 📈 Monitoring des Tests

### Métriques Importantes

- **Temps d'exécution** : Surveillance des tests lents
- **Couverture de code** : Maintien d'un taux élevé
- **Consommation mémoire** : Optimisation des ressources Docker

### Outils Recommandés

```bash
# Profiling des tests
docker compose --profile test exec php-test php -d variables_order=EGPCS vendor/bin/phpunit --profile

# Analyse de la couverture
docker compose --profile test exec php-test php -d variables_order=EGPCS vendor/bin/phpunit --coverage-html coverage/

# Métriques de performance
docker compose --profile test exec php-test php bin/console debug:container --env=test
```

## 📨 AMQP (ext-amqp)

- Les images PHP (dev et test) incluent l'extension `amqp` compilée via PECL et la lib `rabbitmq-c`.
- Vérifier l'installation dans un conteneur:
  ```bash
  docker compose exec php-test php --ri amqp
  ```
- DSN par défaut:
  - `MESSENGER_TRANSPORT_DSN=amqp://admin:password123@rabbitmq:5672/%2f/messages`

## 🎯 Conclusion

L'environnement Docker permet une exécution fiable et reproductible des tests sur toutes les plateformes. L'isolation des services et la séparation des environnements garantissent la qualité et la stabilité du code.
