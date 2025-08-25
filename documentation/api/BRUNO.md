# 🧰 Bruno — Tests manuels de l’API

## Importer
- Option A: Importer `openapi.json` directement dans Bruno
- Option B: Importer la collection Postman `postman_collection.json`

## Configuration
- Base URL: `http://localhost:8080`
- Variables d'environnement Bruno: `base_url`, `email`, `password`

## Scénarios typiques
- Auth: register → login → me → logout
- Événements: list → show → create → update → publish → delete
- Inscriptions: register to event → confirm/cancel → listes confirmed/waitlist

Notes Windows: voir [../setup/SETUP_DOCKER.md](../setup/SETUP_DOCKER.md) pour l'accès aux services (ports, WSL2) et bonnes pratiques.
