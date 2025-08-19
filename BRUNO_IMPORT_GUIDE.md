# 📋 Guide d'Import OpenAPI dans Bruno

## 🚀 Import Rapide

### **1. Fichier OpenAPI Disponible**
Le fichier `openapi.json` est prêt à l'import dans Bruno :
- **Emplacement** : `c:\wamp64\www\friendsapp\openapi.json`
- **Format** : OpenAPI 3.0.0
- **Contenu** : Tous les endpoints d'authentification et d'événements

### **2. Procédure d'Import dans Bruno**

#### **Étape 1 : Ouvrir Bruno**
```bash
# Lancer Bruno
bruno
```

#### **Étape 2 : Créer une nouvelle Collection**
1. Cliquer sur **"New Collection"**
2. Nommer : `EventApp API`
3. Choisir l'emplacement de sauvegarde

#### **Étape 3 : Importer le fichier OpenAPI**
1. Clic droit sur la collection → **"Import"**
2. Sélectionner **"OpenAPI v3"**
3. Choisir le fichier : `c:\wamp64\www\friendsapp\openapi.json`
4. Cliquer sur **"Import"**

## 📚 Endpoints Importés

### **Authentication**
- `POST /api/register` - Inscription utilisateur
- `POST /api/login` - Connexion
- `GET /api/me` - Profil utilisateur
- `POST /api/logout` - Déconnexion

### **Events**
- `GET /api/events` - Liste des événements
- `POST /api/events` - Créer un événement
- `GET /api/events/{id}` - Détails d'un événement
- `PUT /api/events/{id}` - Modifier un événement
- `PATCH /api/events/{id}/publish` - Publier un événement
- `DELETE /api/events/{id}` - Supprimer un événement
- `GET /api/events/popular` - Événements populaires
- `GET /api/events/upcoming` - Événements à venir

## ⚙️ Configuration Bruno

### **Variables d'Environnement**
Après l'import, configurer les variables :

```json
{
  "base_url": "http://localhost:8000",
  "auth_token": "",
  "user_email": "test@example.com",
  "user_password": "motdepasse123"
}
```

### **Scripts de Pré-requête**
Pour l'authentification automatique :

```javascript
// Script global pour maintenir la session
if (bru.getVar("auth_token")) {
  req.setHeader("Authorization", "Bearer " + bru.getVar("auth_token"));
}
```

### **Scripts de Post-requête**
Pour capturer les tokens :

```javascript
// Après login réussi
if (res.status === 200 && res.body.token) {
  bru.setVar("auth_token", res.body.token);
}
```

## 🧪 Tests Automatisés

### **Scénario de Test Complet**

#### **1. Inscription**
```javascript
// Test d'inscription
test("User registration", function() {
  expect(res.status).to.equal(201);
  expect(res.body).to.have.property('user');
  expect(res.body.user).to.have.property('email');
});
```

#### **2. Connexion**
```javascript
// Test de connexion
test("User login", function() {
  expect(res.status).to.equal(200);
  expect(res.body).to.have.property('message');
  expect(res.body).to.have.property('user');
});
```

#### **3. Création d'Événement**
```javascript
// Test création événement
test("Create event", function() {
  expect(res.status).to.equal(201);
  expect(res.body).to.have.property('id');
  expect(res.body.title).to.equal("Conférence Tech 2024");
});
```

## 🔧 Avantages de l'Import OpenAPI

### **✅ Gain de Temps**
- **Import automatique** de tous les endpoints
- **Paramètres pré-configurés** avec exemples
- **Validation automatique** des schémas

### **✅ Documentation Intégrée**
- **Descriptions** de chaque endpoint
- **Exemples de requêtes** prêts à l'emploi
- **Codes de réponse** documentés

### **✅ Maintenance Simplifiée**
- **Synchronisation** avec les modifications API
- **Cohérence** entre documentation et tests
- **Réutilisabilité** sur différents environnements

## 🚀 Utilisation Avancée

### **Environnements Multiples**
```json
{
  "development": {
    "base_url": "http://localhost:8000"
  },
  "staging": {
    "base_url": "https://staging.eventapp.com"
  },
  "production": {
    "base_url": "https://api.eventapp.com"
  }
}
```

### **Tests de Charge**
```javascript
// Configuration pour tests de performance
const iterations = 100;
const concurrent = 10;

for (let i = 0; i < iterations; i++) {
  // Exécuter les requêtes
}
```

## 📊 Monitoring et Debug

### **Logs Détaillés**
- **Temps de réponse** pour chaque endpoint
- **Codes d'erreur** avec détails
- **Payload** des requêtes/réponses

### **Métriques**
- **Taux de succès** par endpoint
- **Performance moyenne** des API
- **Détection d'anomalies** automatique

---

## 🎯 Prêt à Tester !

Votre fichier OpenAPI est optimisé pour Bruno avec :
- ✅ **Schémas complets** et validés
- ✅ **Exemples réalistes** pour chaque endpoint
- ✅ **Documentation intégrée** en français
- ✅ **Structure professionnelle** respectant les standards

**Import le fichier et commencez vos tests immédiatement !** 🚀
