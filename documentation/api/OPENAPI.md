# 📘 OpenAPI (Nelmio) — Génération & Cohérence

## Générer `openapi.json`
```bash
php bin/console nelmio:apidoc:dump --format=json --no-interaction > openapi.json
```

## Checklist de cohérence (Routes ↔ OpenAPI ↔ Tests)
- [ ] Tous les endpoints du code sont documentés
- [ ] Endpoints imbriqués documentés:
  - `/api/events/{id}/registrations`
  - `/api/events/{id}/registrations/confirmed`
  - `/api/events/{id}/registrations/waitlist`
- [ ] Codes HTTP et schémas alignés
- [ ] Tests présents pour Auth, Users, Registrations (incluant nested)

## Bonnes pratiques d'annotation
- Utiliser les attributs Nelmio/OpenAPI sur les actions de contrôleurs (`src/Controller/Api/*Controller.php`)
- Décrire paramètres path/query et bodies, réponses 2xx/4xx/5xx

## Vérification rapide
Après génération, rechercher les chemins ci-dessus dans `openapi.json`. Si absents: la doc est obsolète → compléter les annotations, puis regénérer.
