# 🪟 Guide Bruno - Accès depuis Windows

## 🎯 Configuration pour Windows + WSL2 + Docker

### **1. Architecture Actuelle**
```
Windows 10/11
├── Bruno (application Windows)
├── WSL2 Ubuntu
│   ├── Docker Desktop
│   ├── friendsapp/ (projet)
│   └── Services Docker
│       ├── nginx:8080
│       ├── mysql:3306
│       ├── rabbitmq:5672,15672
│       └── phpmyadmin:8081
```

### **2. Accès API depuis Bruno Windows**

#### **✅ URL de Base Correcte**
```
http://localhost:8080
```

**Pourquoi ça fonctionne :**
- Docker Desktop expose automatiquement les ports WSL vers Windows
- `localhost:8080` depuis Windows = nginx Docker dans WSL
- Pas besoin de configuration réseau supplémentaire

#### **⚠️ Anciennes URLs (ne plus utiliser)**
```
❌ http://localhost:8000  (Symfony serve - plus utilisé)
❌ http://wsl.localhost:8080  (pas nécessaire)
❌ http://127.0.0.1:8080  (équivalent mais moins lisible)
```

---

## 📁 **Accès aux Fichiers depuis Windows**

### **1. Fichier OpenAPI pour Bruno**

#### **Chemin Windows :**
```
\\wsl$\Ubuntu\home\julien\www\friendsapp\openapi.json
```

#### **Alternative via Explorateur :**
1. Ouvrir l'Explorateur Windows
2. Taper dans la barre d'adresse : `\\wsl$\Ubuntu`
3. Naviguer vers : `home\julien\www\friendsapp\`
4. Sélectionner `openapi.json`

### **2. Synchronisation Automatique**
- Les fichiers sont **automatiquement synchronisés** entre WSL et Windows
- Modifications dans WSL = visibles immédiatement dans Windows
- Pas besoin de copier/coller manuellement

---

## 🔧 **Configuration Bruno Optimale**

### **Variables d'Environnement**
```json
{
  "base_url": "http://localhost:8080",
  "user_email": "test@example.com",
  "user_password": "motdepasse123",
  "phpmyadmin_url": "http://localhost:8081",
  "rabbitmq_url": "http://localhost:15672"
}
```

### **Headers Globaux (Optionnel)**
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

---

## 🧪 **Test de Connectivité**

### **1. Vérification des Services**
Depuis Windows (PowerShell ou CMD) :

```powershell
# Test API
curl http://localhost:8080

# Test phpMyAdmin
curl http://localhost:8081

# Test RabbitMQ Management
curl http://localhost:15672
```

### **2. Vérification depuis WSL**
```bash
# Dans WSL Ubuntu
docker compose ps
curl http://localhost:8080
```

### **3. Résultats Attendus**
- **API (8080)** : Page d'accueil Symfony ou réponse JSON
- **phpMyAdmin (8081)** : Interface de connexion phpMyAdmin
- **RabbitMQ (15672)** : Interface de management RabbitMQ

---

## 🚨 **Dépannage Réseau**

### **Problème : "Connection refused" depuis Windows**

#### **Solution 1 : Vérifier Docker Desktop**
```powershell
# Vérifier que Docker Desktop est démarré
docker --version
docker compose --version
```

#### **Solution 2 : Redémarrer les Services**
```bash
# Dans WSL
cd /home/julien/www/friendsapp
docker compose down
docker compose up -d
```

#### **Solution 3 : Vérifier les Ports**
```powershell
# Depuis Windows - vérifier les ports ouverts
netstat -an | findstr :8080
netstat -an | findstr :8081
```

### **Problème : "File not found" pour openapi.json**

#### **Solution : Vérifier le Chemin WSL**
```powershell
# Depuis Windows
dir "\\wsl$\Ubuntu\home\julien\www\friendsapp\openapi.json"
```

#### **Alternative : Copier Temporairement**
```bash
# Dans WSL - copier vers un dossier Windows accessible
cp /home/julien/www/friendsapp/openapi.json /mnt/c/temp/
```

---

## 🎯 **Workflow Recommandé**

### **1. Démarrage Quotidien**
```bash
# Dans WSL Ubuntu
cd /home/julien/www/friendsapp
docker compose up -d
```

### **2. Développement**
1. **Modifier le code** dans WSL (VS Code, Windsurf)
2. **Tester l'API** avec Bruno depuis Windows
3. **Voir la DB** avec phpMyAdmin depuis Windows
4. **Monitorer** RabbitMQ depuis Windows

### **3. Arrêt**
```bash
# Dans WSL
docker compose down
```

---

## 📊 **Avantages de cette Configuration**

### **✅ Performance**
- Docker natif dans WSL2 = performances optimales
- Pas de virtualisation supplémentaire

### **✅ Simplicité**
- Un seul `docker compose up -d` pour tout démarrer
- Accès direct depuis Windows sans configuration réseau

### **✅ Portabilité**
- Même configuration sur tous les appareils
- Pas de dépendance à l'environnement Windows local

### **✅ Isolation**
- Services isolés dans Docker
- Pas de conflit avec d'autres projets

---

## 🎉 **Prêt pour Bruno !**

Votre configuration est optimale pour tester l'API depuis Bruno Windows :

1. **Importer** `\\wsl$\Ubuntu\home\julien\www\friendsapp\openapi.json`
2. **Configurer** l'URL de base : `http://localhost:8080`
3. **Tester** tous vos endpoints directement

**Aucune configuration réseau supplémentaire nécessaire !** 🚀
