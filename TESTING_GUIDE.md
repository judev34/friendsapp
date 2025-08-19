# 🧪 Guide de Test en Conditions Réelles - EventApp

## 📋 Prérequis

### **1. Environnement Technique**
- **WAMP/XAMPP** : Apache + MariaDB + PHP 8.2+
- **Composer** : Gestionnaire de dépendances PHP
- **Symfony CLI** (optionnel mais recommandé)
- **Postman/Insomnia** : Test des API REST
- **Git** : Contrôle de version

### **2. Configuration Base de Données**
```sql
-- Créer la base de données
CREATE DATABASE friendsapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer un utilisateur dédié (optionnel)
CREATE USER 'friendsapp_user'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON friendsapp.* TO 'friendsapp_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🚀 Démarrage du Projet

### **1. Installation des Dépendances**
```bash
cd c:\wamp64\www\friendsapp
composer install
```

### **2. Configuration Environnement**
Créer le fichier `.env.local` :
```env
# Base de données
DATABASE_URL="mysql://root@127.0.0.1:3306/friendsapp?serverVersion=10.11&charset=utf8mb4"

# Environnement
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=your-secret-key-here

# Mailer (pour les tests d'emails)
MAILER_DSN=smtp://localhost:1025

# Slack (optionnel)
SLACK_WEBHOOK_URL=
```

### **3. Initialisation Base de Données**
```bash
# Créer les tables
php bin/console doctrine:migrations:migrate

# Vérifier le schéma
php bin/console doctrine:schema:validate
```

### **4. Démarrage du Serveur**
```bash
# Avec Symfony CLI (recommandé)
symfony serve -d

# Ou avec PHP built-in server
php -S localhost:8000 -t public/
```

## 🔧 Tests Fonctionnels

### **1. Test d'Inscription Utilisateur**

**Endpoint :** `POST /api/register`

**Payload :**
```json
 
```

**Tests à effectuer :**
- ✅ Inscription réussie (201)
- ❌ Email déjà existant (409)
- ❌ Mot de passe trop court (400)
- ❌ Email invalide (400)
- ❌ Champs manquants (400)

### **2. Test de Connexion**

**Endpoint :** `POST /api/login`

**Payload :**
```json
{
    "email": "test@example.com",
    "password": "motdepasse123"
}
```

**Tests à effectuer :**
- ✅ Connexion réussie (200)
- ❌ Identifiants incorrects (401)
- ❌ Utilisateur inexistant (401)

### **3. Test Profil Utilisateur**

**Endpoint :** `GET /api/me`

**Headers :** Session cookie après connexion

**Tests à effectuer :**
- ✅ Récupération profil connecté (200)
- ❌ Accès sans authentification (401)

## 🎯 Scénarios de Test Complets

### **Scénario 1 : Cycle Utilisateur Complet**
1. **Inscription** → Vérifier création en BDD
2. **Connexion** → Vérifier session
3. **Profil** → Vérifier données
4. **Déconnexion** → Vérifier invalidation session

### **Scénario 2 : Gestion d'Événements**
1. **Créer événement** (à implémenter)
2. **Lister événements**
3. **Modifier événement**
4. **Supprimer événement**

### **Scénario 3 : Système d'Inscription**
1. **S'inscrire à un événement**
2. **Vérifier places disponibles**
3. **Annuler inscription**
4. **Gestion liste d'attente**

## 🛠️ Outils de Test

### **1. Scripts cURL**
```bash
# Inscription
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"motdepasse123","firstName":"Jean","lastName":"Dupont"}'

# Connexion
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"email":"test@example.com","password":"motdepasse123"}'

# Profil
curl -X GET http://localhost:8000/api/me \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

### **2. Collection Postman**
Créer une collection avec :
- Variables d'environnement (base_url, tokens)
- Tests automatisés
- Scripts de pré/post-requête
- Gestion des cookies/sessions

### **3. Tests avec PHPUnit**
```bash
# Lancer les tests
php bin/phpunit

# Tests avec couverture
php bin/phpunit --coverage-html coverage/
```

## 📊 Monitoring et Debug

### **1. Logs Symfony**
```bash
# Voir les logs en temps réel
tail -f var/log/dev.log

# Logs par niveau
grep "ERROR" var/log/dev.log
```

### **2. Profiler Symfony**
Accéder à `http://localhost:8000/_profiler` après chaque requête

### **3. Base de Données**
```sql
-- Vérifier les utilisateurs créés
SELECT * FROM user;

-- Vérifier les événements
SELECT * FROM event;

-- Vérifier les inscriptions
SELECT * FROM registration;
```

## 🔍 Tests de Sécurité

### **1. Validation des Données**
- Injection SQL
- XSS dans les champs
- CSRF sur les formulaires
- Validation des types de données

### **2. Authentification**
- Accès aux routes protégées
- Gestion des sessions
- Expiration des tokens
- Tentatives de brute force

### **3. Autorisation**
- Accès aux ressources d'autres utilisateurs
- Élévation de privilèges
- Rôles et permissions

## 🚨 Tests de Charge

### **1. Apache Bench**
```bash
# Test de charge sur l'inscription
ab -n 100 -c 10 -p register.json -T application/json http://localhost:8000/api/register
```

### **2. JMeter**
- Créer un plan de test
- Simuler plusieurs utilisateurs simultanés
- Mesurer les temps de réponse

## 📈 Métriques à Surveiller

### **Performance**
- Temps de réponse API (< 200ms)
- Utilisation mémoire PHP
- Requêtes SQL (éviter N+1)
- Cache hit ratio

### **Fonctionnel**
- Taux de réussite des inscriptions
- Erreurs 4xx/5xx
- Validation des données
- Intégrité des données

## 🎯 Checklist de Test

### **Avant Production**
- [ ] Tous les endpoints API fonctionnent
- [ ] Validation des données côté serveur
- [ ] Gestion d'erreurs appropriée
- [ ] Logs configurés correctement
- [ ] Base de données optimisée
- [ ] Sécurité testée (OWASP Top 10)
- [ ] Tests de charge passés
- [ ] Documentation API à jour
- [ ] Monitoring en place
- [ ] Sauvegarde BDD configurée

### **Tests Automatisés**
- [ ] Tests unitaires (> 80% couverture)
- [ ] Tests d'intégration
- [ ] Tests fonctionnels
- [ ] Tests de sécurité
- [ ] Tests de performance

## 🔧 Dépannage

### **Problèmes Courants**
1. **Erreur 500** → Vérifier les logs Symfony
2. **Connexion BDD** → Vérifier DATABASE_URL
3. **Autoloader** → `composer dump-autoload`
4. **Cache** → `php bin/console cache:clear`
5. **Permissions** → Vérifier var/ et public/

### **Debug Avancé**
```bash
# Mode debug activé
APP_DEBUG=true

# Profiler activé
php bin/console debug:router
php bin/console debug:container
php bin/console doctrine:mapping:info
```

---

## 🎉 Prêt pour les Tests !

Suivez ce guide étape par étape pour tester votre application EventApp en conditions réelles. N'hésitez pas à adapter les tests selon vos besoins spécifiques.

**Bon testing ! 🚀**
