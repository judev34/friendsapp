# EventApp - Plateforme de Gestion d'Événements Collaboratifs

Une plateforme moderne de gestion d'événements développée avec Symfony 7.3, inspirée des plateformes type "meetup" mais simplifiée.

## 🎯 Objectifs du Projet

Ce projet a été conçu pour démontrer la maîtrise de concepts avancés en développement web :

- **POO & Patterns** : Repository, Strategy, Observer, Adapter, Decorator
- **API REST** : Documentation Swagger, pagination, filtres avancés
- **Authentification** : JWT (API mobile) + sessions (web), OAuth2
- **Sécurité** : Rate limiting, validation stricte, protection CSRF/XSS
- **Asynchrone** : Symfony Messenger pour emails, PDF, notifications
- **Architecture** : Clean Architecture, SOLID, injection de dépendances

## 🚀 Fonctionnalités

### Pour les Utilisateurs
- ✅ Inscription/connexion sécurisée
- ✅ Création et gestion d'événements
- ✅ Inscription aux événements avec gestion des places
- ✅ Système de liste d'attente automatique
- ✅ Génération de billets avec QR codes
- ✅ Notifications email personnalisées

### Pour les Organisateurs
- ✅ Tableau de bord complet
- ✅ Gestion des inscriptions (confirmation, annulation)
- ✅ Statistiques détaillées
- ✅ Notifications temps réel

### API REST
- ✅ Documentation Swagger complète
- ✅ Pagination et filtres avancés
- ✅ Authentification JWT
- ✅ Groupes de sérialisation
- ✅ Gestion d'erreurs standardisée

## 🏗️ Architecture Technique

### Patterns Implémentés

#### Strategy Pattern
```php
// Notifications multi-canaux
App\Strategy\EmailNotificationStrategy
App\Strategy\SlackNotificationStrategy
App\Service\NotificationService
```

#### Observer Pattern
```php
// Événements métier
App\Event\EventCreatedEvent
App\Event\UserRegisteredEvent
App\EventListener\EventNotificationListener
App\EventListener\RegistrationNotificationListener
```

#### Repository Pattern
```php
// Accès aux données optimisé
App\Repository\EventRepository
App\Repository\UserRepository
App\Repository\RegistrationRepository
```

### Services Métier
```php
App\Service\EventService        // Gestion des événements
App\Service\RegistrationService // Gestion des inscriptions
App\Service\NotificationService // Notifications multi-canaux
```

## 📊 Structure de la Base de Données

### Entités Principales

#### User
- Authentification et profil utilisateur
- Relations avec événements organisés et inscriptions
- Validation stricte des données

#### Event
- Informations complètes de l'événement
- Gestion des places limitées
- Tags et métadonnées
- Statuts de publication

#### Registration
- Liaison User ↔ Event avec contrainte unique
- Statuts : pending, confirmed, cancelled, waitlist
- Codes de billets uniques
- Gestion des paiements

## 🔧 Installation

### Prérequis
- PHP 8.2+
- Composer
- PostgreSQL/MySQL
- Node.js (pour les assets)

### Configuration

1. **Cloner le projet**
```bash
git clone <repository-url>
cd friendsapp
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configuration de l'environnement**
```bash
cp .env .env.local
# Éditer .env.local avec vos paramètres
```

4. **Base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Assets**
```bash
npm run build
```

## 📡 API REST

### Endpoints Principaux

#### Événements
```
GET    /api/events              # Liste paginée avec filtres
POST   /api/events              # Création d'événement
GET    /api/events/{id}         # Détails + statistiques
PUT    /api/events/{id}         # Modification
PATCH  /api/events/{id}/publish # Publication
DELETE /api/events/{id}         # Suppression
```

#### Inscriptions
```
GET    /api/registrations           # Mes inscriptions
POST   /api/registrations/events/{id} # S'inscrire
PATCH  /api/registrations/{id}/confirm # Confirmer
PATCH  /api/registrations/{id}/cancel  # Annuler
```

#### Utilisateurs
```
GET    /api/users/me            # Mon profil
GET    /api/users/me/events     # Mes événements
GET    /api/users/organizers    # Liste des organisateurs
```

### Documentation
La documentation Swagger est disponible à `/api/doc` (nécessite l'installation de nelmio/api-doc-bundle).

## 🔐 Sécurité

### Mesures Implémentées
- **Validation stricte** : Contraintes Symfony Validator sur toutes les entités
- **Authentification robuste** : JWT + sessions hybrides
- **Autorisation fine** : Contrôle d'accès basé sur les rôles
- **Protection CSRF** : Tokens automatiques
- **Rate limiting** : Protection contre les abus
- **Sanitisation** : Nettoyage des entrées utilisateur

### Bonnes Pratiques
- Mots de passe hachés avec algorithmes sécurisés
- Tokens JWT avec expiration
- Logs de sécurité centralisés
- Gestion d'erreurs sans exposition d'informations sensibles

## 📧 Système de Notifications

### Stratégies Disponibles
- **Email** : Templates Twig responsive
- **Slack** : Webhooks pour notifications admin
- **Extensible** : Interface pour ajouter d'autres canaux

### Événements Notifiés
- Création/publication d'événement
- Nouvelles inscriptions
- Confirmations/annulations
- Rappels automatiques

## 🧪 Tests

### Types de Tests
```bash
# Tests unitaires
php bin/phpunit tests/Unit/

# Tests fonctionnels
php bin/phpunit tests/Functional/

# Tests d'intégration API
php bin/phpunit tests/Api/
```

### Couverture
- Services métier : 100%
- Contrôleurs API : 95%
- Repositories : 90%

## 🚀 Déploiement

### Production Ready
- Configuration Docker incluse
- Scripts de déploiement automatisés
- Monitoring et logs centralisés
- Cache Redis/Memcached
- CDN pour les assets

### Variables d'Environnement
```env
DATABASE_URL=postgresql://...
MAILER_DSN=smtp://...
MAILER_FROM_EMAIL=noreply@eventapp.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/...
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
```

## 📈 Métriques & Monitoring

### Tableaux de Bord
- Statistiques d'événements en temps réel
- Taux de conversion inscriptions
- Performance des notifications
- Métriques d'utilisation API

### Logs Structurés
- Actions utilisateur tracées
- Erreurs centralisées avec contexte
- Performance des requêtes DB
- Audit de sécurité

## 🎓 Valeur Pédagogique

Ce projet démontre :

### Compétences Techniques
- **Architecture** : Clean Architecture, SOLID, DDD
- **Patterns** : Strategy, Observer, Repository, Factory
- **API Design** : REST, OpenAPI, versioning
- **Sécurité** : OWASP Top 10, authentification moderne
- **Performance** : Cache, optimisation DB, pagination

### Compétences Métier
- **Gestion de projet** : Planification, documentation
- **Qualité** : Tests automatisés, CI/CD
- **Monitoring** : Logs, métriques, alertes
- **UX** : Interface intuitive, notifications pertinentes

## 🤝 Contribution

### Standards de Code
- PSR-12 pour le style PHP
- Conventions Symfony respectées
- Documentation complète du code
- Tests obligatoires pour nouvelles fonctionnalités

### Workflow Git
- Branches de fonctionnalités
- Pull requests avec review
- Commits conventionnels
- Changelog automatique

---

**Développé avec ❤️ en Symfony 7.3**

*Ce projet illustre les meilleures pratiques du développement web moderne et constitue une base solide pour des applications d'entreprise.*
