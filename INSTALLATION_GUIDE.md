# 🚀 Guide d'Installation - Friends App

Guide complet pour installer le projet sur n'importe quel appareil avec WSL2 + Docker Desktop.

## 📋 Prérequis

### Windows avec WSL2
1. **Docker Desktop** installé et configuré pour WSL2
2. **Ubuntu** (ou autre distribution Linux) dans WSL2
3. **Git** installé dans WSL2

## 🔧 Installation Automatique

### 1. Cloner le projet
```bash
git clone <votre-repo-url> friendsapp
cd friendsapp
```

### 2. Lancer le script d'installation
```bash
chmod +x dev-setup.sh
./dev-setup.sh
```

### 3. Installer les dépendances PHP locales
```bash
# Ajouter le repository PHP
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Installer PHP et extensions
sudo apt install -y php8.2-cli php8.2-xml php8.2-mbstring php8.2-zip php8.2-curl php8.2-mysql php8.2-amqp

# Installer Composer
sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
sudo ln -sf /usr/local/bin/composer /usr/bin/composer
```

### 4. Configurer les variables d'environnement
```bash
# Copier les fichiers d'exemple
cp .env.dev.example .env.dev
cp .env.docker.example .env.docker
cp .env.test.example .env.test

# Éditer .env.dev pour ajouter vos secrets
nano .env.dev
# Remplacer :
# - APP_SECRET=your-secret-key-here
# - SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

### 5. Installer les dépendances du projet
```bash
composer install
```

### 6. Configurer la base de données
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## 🎯 Vérification

Après l'installation, vérifiez que tout fonctionne :

- **Application** : http://localhost:8080
- **RabbitMQ Management** : http://localhost:15672 (admin/password123)
- **phpMyAdmin (Interface MySQL)** : http://localhost:8081 (app/password)
- **Base de données CLI** : `docker compose exec database mysql -u app -ppassword friendsapp`

## 🔧 Commandes Utiles

### Développement quotidien
```bash
# Démarrer l'environnement
docker compose up -d

# Arrêter l'environnement
docker compose down

# Voir les logs
docker compose logs -f

# Accéder à la base de données
docker compose exec database mysql -u app -ppassword friendsapp

# Commandes Symfony (PHP local)
php bin/console make:controller
php bin/console doctrine:migrations:migrate
php bin/console cache:clear

# Ou via Docker si nécessaire
docker compose exec php php bin/console cache:clear
```

### Gestion des dépendances
```bash
# Installer une nouvelle dépendance
composer require nom/package

# Mettre à jour les dépendances
composer update
```

## 🐛 Résolution de Problèmes

### Problème : "could not find driver"
```bash
sudo apt install -y php8.2-mysql php8.2-pdo
```

### Problème : "ext-amqp * -> it is missing"
```bash
sudo apt install -y php8.2-amqp
```

### Problème : Permissions sur vendor/
```bash
sudo chown -R $USER:$USER /path/to/project
```

### Problème : Conteneurs non accessibles
```bash
docker compose down
docker compose up -d
```

## 📁 Structure du Projet

```
friendsapp/
├── docker/              # Configuration Docker
├── src/                 # Code source Symfony
├── config/              # Configuration Symfony
├── templates/           # Templates Twig
├── public/              # Assets publics
├── vendor/              # Dépendances (généré)
├── compose.yaml         # Configuration Docker Compose
├── Dockerfile.dev       # Image Docker pour développement
├── dev-setup.sh         # Script d'installation automatique
└── .env.dev            # Variables d'environnement développement
```

## 🌐 Services Disponibles

| Service | URL | Identifiants |
|---------|-----|--------------|
| Application | http://localhost:8080 | - |
| phpMyAdmin (MySQL GUI) | http://localhost:8081 | app/password |
| RabbitMQ Management | http://localhost:15672 | admin/password123 |
| MySQL CLI | localhost:3306 | app/password |
| Mailpit (emails) | http://localhost:8025 | - |

## 🔄 Workflow de Développement

1. **Démarrer** : `docker compose up -d`
2. **Développer** : Modifier le code dans `src/`
3. **Tester** : `php bin/phpunit`
4. **Base de données** : `php bin/console doctrine:migrations:diff`
5. **Arrêter** : `docker compose down`

## 📝 Notes Importantes

- **vendor/** est géré côté hôte (pas dans Docker)
- **PHP** installé localement pour l'IDE et Composer
- **Services** (MySQL, RabbitMQ) dans Docker
- **Configuration** adaptée pour WSL2 + Docker Desktop
