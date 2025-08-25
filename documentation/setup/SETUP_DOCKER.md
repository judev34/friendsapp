# 🐳 Setup Docker — FriendsApp

Ce guide est la référence actuelle pour l'environnement Docker.

## Démarrage rapide
```bash
docker compose up -d
# App: http://localhost:8080
# API Doc: http://localhost:8080/api/doc
# RabbitMQ: http://localhost:15672 (admin/password123)
```

## Base de données
- Dev (dans les conteneurs): `mysql://app:password@database:3306/friendsapp`
- Test (dans les conteneurs): `mysql://app:password@database-test:3306/friendsapp_test`
- Test (depuis l'hôte): `127.0.0.1:3307`

## Migrations & tests (profil test)
```bash
docker compose --profile test up -d
# Migrations
docker compose --profile test exec -T php-test sh -lc \
 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
  php bin/console doctrine:migrations:migrate -n --env=test'
# Tests
docker compose --profile test exec -T php-test sh -lc \
 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
  php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'
```

Plus de détails dans [../COMMANDS.md](../COMMANDS.md).
