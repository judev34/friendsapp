# 🖥️ Setup Local (sans Docker)

Ce guide est la référence actuelle pour une installation locale (sans Docker).

## Prérequis
- PHP 8.2+, Composer, MySQL

## Étapes
```bash
composer install
cp .env .env.local
# Éditer .env.local (DATABASE_URL, etc.)
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
symfony serve -d  # ou php -S localhost:8000 -t public/
```

- API Doc: http://localhost:8000/api/doc
- Voir aussi: [../COMMANDS.md](../COMMANDS.md)
