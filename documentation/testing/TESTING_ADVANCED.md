# 🧪 Tests avancés

## Couverture
```bash
docker compose --profile test exec -T php-test sh -lc \
 'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
  php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml --coverage-html var/coverage'
```

## Debug & Outils
- Profiler Symfony, logs (`var/log/test.log`), `debug:router`, `debug:container`
- SQL: éviter N+1, `doctrine:mapping:info`

## Performance & sécurité
- ApacheBench/JMeter, OWASP Top 10 checklist

Pour les bases: voir [TESTING.md](TESTING.md).
