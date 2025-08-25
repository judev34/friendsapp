# FriendsApp

Plateforme d'événements (Symfony 7.3) avec API documentée, tests fonctionnels et environnement Docker.

## 🚀 Quick Start (Docker)

```bash
docker compose up -d
# App: http://localhost:8080
# API Doc (Swagger): http://localhost:8080/api/doc
```

- Base de données (dev): MySQL `app/password` sur `localhost:3306`
- RabbitMQ (dev): http://localhost:15672 (admin/password123)

## 📚 Documentation

- Index de la doc: `documentation/INDEX.md`
- Setup Docker: `documentation/setup/SETUP_DOCKER.md`
- Setup local (sans Docker): `documentation/setup/SETUP_LOCAL.md`
- Tests (profil Docker test): `documentation/testing/TESTING.md`
- API (Vue d'ensemble): `documentation/api/API_GUIDE.md`
- OpenAPI/Nelmio: `documentation/api/OPENAPI.md`
- Bruno (client API): `documentation/api/BRUNO.md`
- Commandes/cheatsheet: `documentation/COMMANDS.md`
- Messenger/RabbitMQ: `documentation/operations/MESSENGER_RABBITMQ.md`

## ✅ Qualité & Sécurité

- Conventions Symfony, SOLID, validation des entrées, secrets dans `.env.local` uniquement
- Tests via profil Docker `test` + DB dédiée

---

Consulte la doc dans `documentation/` pour les détails opérationnels, API et tests.
