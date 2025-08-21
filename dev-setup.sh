#!/bin/bash

# Script de configuration pour l'environnement de développement Symfony avec Docker
# Compatible WSL Ubuntu + Docker Desktop
# Workflow : vendor/ géré côté hôte, pas dans le conteneur

set -e

echo "🚀 Configuration de l'environnement de développement Symfony..."

# Vérification que Docker est disponible
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé ou accessible"
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose n'est pas disponible"
    exit 1
fi

# Arrêt des conteneurs existants
echo "🛑 Arrêt des conteneurs existants..."
docker compose down --remove-orphans || true

# Nettoyage des volumes si demandé
if [ "$1" = "--clean" ]; then
    echo "🧹 Nettoyage des volumes..."
    docker compose down -v
    docker volume prune -f
fi

# Reconstruction des images
echo "🔨 Construction des images Docker..."
docker compose build --no-cache php

# Démarrage des services
echo "🏃 Démarrage des services..."
docker compose up -d

# Attente que les services soient prêts
echo "⏳ Attente que les services soient prêts..."
sleep 10

# Création de la base de données et exécution des migrations
echo "🗄️ Configuration de la base de données..."
docker compose exec php php bin/console doctrine:database:create --if-not-exists --no-interaction
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Vérification de l'état des services
echo "✅ Vérification de l'état des services..."
docker compose ps

echo ""
echo "🎉 Configuration terminée !"
echo ""
echo "📋 Prochaines étapes :"
echo "   1. Installer les dépendances : composer install"
echo "   2. Accéder à l'application : http://localhost:8080"
echo ""
echo "📋 Services disponibles :"
echo "   - Application Symfony : http://localhost:8080"
echo "   - RabbitMQ Management : http://localhost:15672 (admin/password123)"
echo "   - Base de données MySQL : localhost:3306"
echo ""
echo "🔧 Commandes utiles :"
echo "   - Logs : docker compose logs -f"
echo "   - Console Symfony : docker compose exec php php bin/console"
echo "   - Composer dans conteneur : docker compose exec php composer"
echo "   - Tests : docker compose exec php php bin/phpunit"
echo ""
