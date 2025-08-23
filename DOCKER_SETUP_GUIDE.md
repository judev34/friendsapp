# Guide de Démarrage Docker - FriendsApp

## 🎯 Objectif

Ce guide vous permet de cloner et démarrer l'application FriendsApp sur n'importe quelle plateforme (Windows, Mac, Linux) en utilisant Docker pour un environnement de développement cohérent.

## 📋 Prérequis

### Installation Docker

#### Windows
1. Télécharger [Docker Desktop for Windows](https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe)
2. Exécuter l'installateur et redémarrer
3. Vérifier l'installation :
   ```cmd
   docker --version
   docker compose version
   ```

#### Mac
1. Télécharger [Docker Desktop for Mac](https://desktop.docker.com/mac/main/amd64/Docker.dmg)
2. Glisser Docker dans Applications
3. Lancer Docker Desktop
4. Vérifier l'installation :
   ```bash
   docker --version
   docker compose version
   ```

#### Linux (Ubuntu/Debian)
```bash
# Installation Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# (Optionnel) Vérifier le plugin Docker Compose v2
docker compose version

# Redémarrer la session
newgrp docker

# Vérification
docker --version
docker compose version
```

### Configuration Système

#### Windows
- **WSL2** : Recommandé pour de meilleures performances
- **Hyper-V** : Activé automatiquement par Docker Desktop
- **Virtualisation** : Activée dans le BIOS

#### Mac
- **macOS 10.15+** : Version minimale supportée
- **4 GB RAM** : Minimum recommandé pour Docker

#### Linux
- **Kernel 3.10+** : Version minimale
- **systemd** : Pour la gestion des services

## 🚀 Installation Rapide

### 1. Clonage du Projet

```bash
# Cloner le repository
git clone https://github.com/votre-username/friendsapp.git
cd friendsapp
```

### 2. Démarrage Automatique

#### Windows
```cmd
# Double-clic sur le fichier ou en ligne de commande
docker-start.bat
```

#### Mac/Linux
```bash
# Rendre le script exécutable
chmod +x docker-start.sh

# Exécuter le script
./docker-start.sh
```

### 3. Vérification

Après le démarrage, vérifiez que tous les services sont actifs :

- **Application** : http://localhost:8080
- **RabbitMQ Management** : http://localhost:15672 (admin/password123)
- **Base de données** : localhost:3306 (app/password)

## 🔧 Configuration Manuelle

Si vous préférez un contrôle total sur le processus :

### 1. Configuration de l'Environnement

```bash
# Copier le fichier d'environnement
cp .env.docker .env

# Éditer si nécessaire (optionnel)
# nano .env  # Linux/Mac
# notepad .env  # Windows
```

### 2. Construction et Démarrage

```bash
# Construction des images
docker compose build

# Démarrage des services
docker compose up -d

# Vérification du statut
docker compose ps
```

### 3. Initialisation de la Base de Données

```bash
# Installation des dépendances
docker compose exec php composer install

# Création et migration de la base
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

## 🏗️ Architecture des Services

### Services Principaux

| Service | Port | Description |
|---------|------|-------------|
| **nginx** | 80, 443 | Serveur web |
| **php** | 9000 | Application Symfony (dev) |
| **php-test** | (interne) | Application Symfony (tests) |
| **database** | 3306 | MySQL principal |
| **database-test** | 3307 (exposé), 3306 (interne) | MySQL pour tests |
| **rabbitmq** | 5672, 15672 | Message broker |

### Volumes Docker

- **database_data** : Données MySQL persistantes
- **database_test_data** : Données de test persistantes
- **rabbitmq_data** : Données RabbitMQ persistantes
- **symfony_var** : Cache et logs Symfony

## 📱 Commandes Utiles

### AMQP (ext-amqp)

- Les images PHP (dev et test) compilent l'extension `amqp` via PECL et installent `rabbitmq-c`.
- Vérification dans un conteneur PHP:
  ```bash
  docker compose exec php php --ri amqp
  ```
- DSN RabbitMQ par défaut (dev): `amqp://admin:password123@rabbitmq:5672/%2f/messages`.

### Gestion des Services

```bash
# Démarrer tous les services
docker compose up -d

# Arrêter tous les services
docker compose down

# Redémarrer un service spécifique
docker compose restart php

# Voir les logs
docker compose logs -f php
docker compose logs rabbitmq
```

### Développement

```bash
# Accès au container PHP
docker compose exec php bash

# Console Symfony
docker compose exec php php bin/console

# Installation de dépendances
docker compose exec php composer install
docker compose exec php composer require package/name

# Cache Symfony
docker compose exec php php bin/console cache:clear
```

### Tests

```bash
# Démarrage avec profil test
docker compose --profile test up -d

# Appliquer les migrations en environnement test (DSN interne conteneur)
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php bin/console doctrine:migrations:migrate -n --env=test'

# Exécution de la suite de tests
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'

# Tests spécifiques
docker compose --profile test exec -T php-test sh -lc \
'APP_ENV=test DATABASE_URL="mysql://app:password@database-test:3306/friendsapp_test" \
 php -d variables_order=EGPCS vendor/bin/phpunit tests/Functional/'
```

> Note: Dans les conteneurs, utilisez `database-test:3306` (port interne). Depuis l'hôte, utilisez `127.0.0.1:3307` si vous lancez les tests hors Docker.

## 🔍 Dépannage

### Problèmes Courants

#### 1. Port déjà utilisé
```bash
# Windows
netstat -ano | findstr :80
taskkill /PID <PID> /F

# Mac/Linux
sudo lsof -i :80
sudo kill -9 <PID>
```

#### 2. Permissions (Linux/Mac)
```bash
# Corriger les permissions
sudo chown -R $USER:$USER .
chmod -R 755 .
```

#### 3. Mémoire insuffisante
```bash
# Augmenter la mémoire Docker Desktop
# Windows/Mac : Docker Desktop > Settings > Resources > Memory

# Linux : Vérifier la mémoire disponible
free -h
```

#### 4. Services non accessibles
```bash
# Vérifier le statut
docker compose ps

# Reconstruire les images
docker compose build --no-cache

# Redémarrer complètement
docker compose down
docker compose up -d
```

### Logs de Diagnostic

```bash
# Logs détaillés
docker compose logs --details

# Logs d'un service spécifique
docker compose logs php

# Suivi en temps réel
docker compose logs -f --tail=100
```

## 🌐 Accès aux Services

### URLs Principales

- **Application Web** : http://localhost
- **API Documentation** : http://localhost/api/doc
- **RabbitMQ Management** : http://localhost:15672

### Connexions Base de Données

#### Développement
```
Host: localhost
Port: 3306
Database: friendsapp
Username: app
Password: password
```

#### Tests
```
Host: localhost
Port: 3307
Database: friendsapp_test
Username: app
Password: password
```

## 🔄 Workflow de Développement

### 1. Démarrage Quotidien

```bash
# Démarrer l'environnement
docker compose up -d

# Vérifier les services
docker compose ps

# Voir les logs si nécessaire
docker compose logs -f php
```

### 2. Développement

```bash
# Modifications de code : rechargement automatique
# Ajout de dépendances
docker compose exec php composer require vendor/package

# Nouvelles migrations
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate
```

### 3. Tests

```bash
# Tests avant commit
docker compose --profile test up -d
docker compose exec php php bin/phpunit
```

### 4. Arrêt

```bash
# Arrêt propre
docker compose down

# Arrêt avec suppression des volumes (attention !)
docker compose down -v
```

## 📊 Performance et Optimisation

### Recommandations Système

| OS | RAM | CPU | Stockage |
|----|-----|-----|----------|
| Windows | 8 GB | 4 cores | 10 GB libre |
| Mac | 8 GB | 4 cores | 10 GB libre |
| Linux | 4 GB | 2 cores | 10 GB libre |

### Optimisations Docker

```bash
# Nettoyage périodique
docker system prune -a

# Optimisation des images
docker compose build --no-cache

# Monitoring des ressources
docker stats
```

## 🎯 Conclusion

Cet environnement Docker garantit :

- **Portabilité** : Fonctionne identiquement sur toutes les plateformes
- **Isolation** : Environnement de développement propre
- **Reproductibilité** : Configuration cohérente pour toute l'équipe
- **Simplicité** : Démarrage en une commande

Pour toute question ou problème, consultez les logs avec `docker compose logs` ou référez-vous à la section dépannage.
