# 📒 FriendsApp — Cheatsheet des commandes

Ce document regroupe les commandes essentielles pour démarrer l'environnement, gérer la base, exécuter les tests, régénérer la doc API, et diagnostiquer les problèmes.

## Docker

- Démarrer/mettre à jour les services (rapide)
```bash
docker compose up -d
```
- Démarrer aussi le profil de test
```bash
docker compose --profile test up -d
```
- Arrêter les services
```bash
docker compose down
```
- Logs temps réel
```bash
docker compose logs -f
```
- Reconstruction complète des images
```bash
docker compose build --no-cache && docker compose up -d
```

## Accès rapides
- Application (Nginx): http://localhost:8080
- phpMyAdmin: http://localhost:8081
- RabbitMQ UI: http://localhost:15672 (admin/password123)

## Console Symfony (containers)
- Exécuter une commande dans le conteneur PHP (dev)
```bash
docker compose exec -T php php bin/console
```
- Exécuter une commande dans le conteneur de test
```bash
docker compose exec -T php-test php bin/console
```

## Base de données
- Variables utiles (interne aux conteneurs):
```
DATABASE_URL (dev)  = mysql://app:password@database:3306/friendsapp
DATABASE_URL (test) = mysql://app:password@database-test:3306/friendsapp_test
```
- Création + migrations (dev)
```bash
docker compose exec -T php php bin/console doctrine:database:create --if-not-exists
docker compose exec -T php php bin/console doctrine:migrations:migrate -n
```
- Création + migrations (test)
```bash
docker compose --profile test up -d
# Option 1: variables au niveau shell
docker compose exec -T php-test sh -lc 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" php bin/console doctrine:database:create --if-not-exists'
docker compose exec -T php-test sh -lc 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" php bin/console doctrine:migrations:migrate -n'
```

## Tests
- Lancer toute la suite PHPUnit (en conteneur test)
```bash
docker compose exec -T php-test sh -lc \
  'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
   php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'
```
- Conseil: forcer le transport Messenger en base (Doctrine) en test
```bash
docker compose exec -T php-test sh -lc \
  'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
   MESSENGER_TRANSPORT_DSN="doctrine://default?queue_name=test_messages" \
   php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'
```

## OpenAPI / Swagger (Nelmio)
- Régénérer `openapi.json`
```bash
php bin/console nelmio:apidoc:dump --format=json --no-interaction > openapi.json
```
- Vérifier que les endpoints suivants apparaissent (sinon: spec obsolète):
```
/api/events/{id}/registrations
/api/events/{id}/registrations/confirmed
/api/events/{id}/registrations/waitlist
```

## Messenger / Async
- DSN dev (AMQP via RabbitMQ) — défini dans `compose.yaml`:
```
MESSENGER_TRANSPORT_DSN=amqp://admin:password123@rabbitmq:5672/%2f/messages
```
- Consommer les messages (si des dispatch async sont actifs)
```bash
docker compose exec -T php php bin/console messenger:consume async -vv
```
- File des messages en échec (Doctrine)
```bash
docker compose exec -T php php bin/console messenger:failed:show
```

## Divers
- Vider le cache Symfony (dev)
```bash
docker compose exec -T php php bin/console cache:clear
```
- Lancer un shell dans un conteneur
```bash
docker compose exec php sh
```

---

Note: N'insérez pas de secrets en clair dans les docs/README; utilisez `.env.local` pour vos valeurs sensibles.
