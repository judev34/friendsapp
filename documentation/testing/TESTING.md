# 🧪 Tests — Chemin standard (profil Docker test)

## Démarrer les services de test
```bash
docker compose --profile test up -d
```

## Migrations test (DSN interne au conteneur)
```bash
docker compose --profile test exec -T php-test sh -lc \
 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
  php bin/console doctrine:migrations:migrate -n --env=test'
```

## Exécuter la suite PHPUnit
```bash
docker compose --profile test exec -T php-test sh -lc \
 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
  php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'
```

Tips
- Forcer Messenger en Doctrine en test si nécessaire (évite AMQP): voir `documentation/COMMANDS.md`.
- Accès DB depuis l'hôte: `127.0.0.1:3307`.

Pour les scénarios avancés: `testing/TESTING_ADVANCED.md`.
