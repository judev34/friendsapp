# 📡 API — Vue d'ensemble

- Base URL (Docker): `http://localhost:8080`
- Doc Swagger: `/api/doc`
- Spécification exportée: `openapi.json`

## Authentification
- Endpoints: `/api/register`, `/api/login`, `/api/me`, `/api/logout`
- Sessions (web). JWT possible (évolutif).

## Domaines
- Événements: `/api/events`, `/api/events/{id}`, `/api/events/{id}/publish`, etc.
- Inscriptions: `/api/registrations*`, `/api/events/{id}/registrations*`
- Utilisateurs: `/api/users/*`

Voir aussi: `api/OPENAPI.md` (génération et checklist) et `api/BRUNO.md` (tests manuels via Bruno).
