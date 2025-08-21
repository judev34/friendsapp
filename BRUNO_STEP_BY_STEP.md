# 📋 Guide Bruno - Étapes Détaillées

## 🚀 **Étape 1 : Import du Fichier OpenAPI**

### **1.1 Ouvrir Bruno**
- Lancer l'application Bruno
- Cliquer sur **"New Collection"**
- Nommer : `EventApp API`

### **1.2 Importer le Fichier**
1. **Clic droit** sur la collection `EventApp API`
2. Sélectionner **"Import"** dans le menu
3. Choisir **"OpenAPI v3"**
4. Naviguer vers : `\\wsl$\Ubuntu\home\julien\www\friendsapp\openapi.json`
5. Cliquer **"Import"**

✅ **Résultat** : Tous vos endpoints sont maintenant visibles dans Bruno

---

## ⚙️ **Étape 2 : Configuration des Variables**

### **2.1 Créer les Variables d'Environnement**
1. Cliquer sur l'icône **"Environments"** (engrenage) en haut
2. Cliquer **"New Environment"**
3. Nommer : `Development`
4. Ajouter ces variables :

```json
{
  "base_url": "http://localhost:8080",
  "user_email": "test@example.com",
  "user_password": "motdepasse123"
}
```

### **2.2 Activer l'Environnement**
- Sélectionner `Development` dans le dropdown des environnements

---

## 🧪 **Étape 3 : Configuration des Scripts (Optionnel)**

### **3.1 Script Global de Collection**
1. **Clic droit** sur la collection `EventApp API`
2. Sélectionner **"Settings"**
3. Onglet **"Scripts"**
4. Dans **"Pre Request Script"**, ajouter :

```javascript
// Maintenir la session pour les requêtes authentifiées
if (bru.getVar("session_cookie")) {
  req.setHeader("Cookie", bru.getVar("session_cookie"));
}
```

### **3.2 Script pour la Connexion**
1. Ouvrir la requête **"POST /api/login"**
2. Onglet **"Scripts"**
3. Dans **"Post Response Script"**, ajouter :

```javascript
// Capturer le cookie de session après connexion
if (res.status === 200) {
  const setCookieHeader = res.getHeader('set-cookie');
  if (setCookieHeader) {
    // Extraire le cookie PHPSESSID
    const sessionCookie = setCookieHeader.find(cookie => cookie.includes('PHPSESSID'));
    if (sessionCookie) {
      bru.setVar("session_cookie", sessionCookie.split(';')[0]);
    }
  }
}
```

**⚠️ Note Importante :** 
- J'ai corrigé la configuration Symfony pour permettre les sessions (stateless: false)
- Videz le cache Symfony après modification : `php bin/console cache:clear`
- Redémarrez votre serveur Symfony

---

## 🎯 **Étape 4 : Tests de Base**

### **4.1 Test d'Inscription**
1. Ouvrir **"POST /api/register"**
2. Dans le **Body**, utiliser :
```json
{
  "email": "{{user_email}}",
  "password": "{{user_password}}",
  "firstName": "Jean",
  "lastName": "Dupont"
}
```
3. Cliquer **"Send"**
4. Vérifier : Status 201 et réponse avec `user`

### **4.2 Test de Connexion**
1. Ouvrir **"POST /api/login"**
2. Dans le **Body**, utiliser :
```json
{
  "email": "{{user_email}}",
  "password": "{{user_password}}"
}
```
3. Cliquer **"Send"**
4. Vérifier : Status 200 et réponse avec `message` et `user`

### **4.3 Test du Profil**
1. Ouvrir **"GET /api/me"**
2. Cliquer **"Send"** (le cookie de session sera automatiquement envoyé)
3. Vérifier : Status 200 et données utilisateur

---

## 🔧 **Étape 5 : Tests Avancés (Optionnel)**

### **5.1 Ajouter des Tests Automatisés**
Pour chaque requête, dans l'onglet **"Tests"** :

#### **Pour l'inscription :**
```javascript
test("User registration successful", function() {
  expect(res.status).to.equal(201);
  expect(res.body).to.have.property('user');
  expect(res.body.user).to.have.property('email');
  expect(res.body.user.email).to.equal(bru.getVar("user_email"));
});
```

#### **Pour la connexion :**
```javascript
test("User login successful", function() {
  expect(res.status).to.equal(200);
  expect(res.body).to.have.property('message');
  expect(res.body).to.have.property('user');
  expect(res.body.user).to.have.property('email');
});
```

#### **Pour le profil :**
```javascript
test("Get user profile", function() {
  expect(res.status).to.equal(200);
  expect(res.body).to.have.property('user');
  expect(res.body.user).to.have.property('id');
});
```

---

## 📊 **Étape 6 : Workflow de Test Complet**

### **6.1 Ordre d'Exécution**
1. **Inscription** → Créer un compte
2. **Connexion** → Obtenir la session
3. **Profil** → Vérifier l'authentification
4. **Création d'événement** → Tester les fonctionnalités

### **6.2 Test de Création d'Événement**
1. Ouvrir **"POST /api/events"**
2. Dans le **Body** :
```json
{
  "title": "Conférence Tech 2024",
  "description": "Une conférence sur les dernières technologies",
  "startDate": "2024-06-15T14:00:00Z",
  "endDate": "2024-06-15T18:00:00Z",
  "location": "Paris, France",
  "maxParticipants": 100,
  "price": 25.99,
  "tags": ["tech", "conference"]
}
```
3. Cliquer **"Send"**
4. Vérifier : Status 201 et événement créé

---

## 🚨 **Dépannage Courant**

### **Erreur 401 (Non authentifié)**
- Vérifier que vous êtes connecté avec **POST /api/login**
- Vérifier que le script de session fonctionne
- Vérifier que l'environnement `Development` est sélectionné

### **Erreur 400 (Données invalides)**
- Vérifier le format JSON dans le Body
- Vérifier que tous les champs requis sont présents
- Vérifier les types de données (string, integer, etc.)

### **Erreur de connexion**
- Vérifier que Docker est démarré : `docker compose up -d`
- Vérifier l'URL de base : `http://localhost:8080`
- Vérifier que les conteneurs sont actifs : `docker compose ps`

---

## ✅ **Checklist de Validation**

- [x] Collection importée avec succès
- [x] Variables d'environnement configurées
- [x] Inscription fonctionne (201)
- [x] Connexion fonctionne (200)
- [x] Profil accessible (200)
- [x] Création d'événement fonctionne (201)
- [ ] Tests automatisés ajoutés
- [x] Scripts de session configurés

---

## 🎉 **Prêt à Tester !**

Votre configuration Bruno est maintenant complète. Vous pouvez :
- Tester tous les endpoints automatiquement
- Utiliser les variables pour différents environnements
- Exécuter des tests automatisés
- Déboguer facilement avec les scripts

**Commencez par l'inscription, puis la connexion, et explorez tous les endpoints !** 🚀
