#!/bin/bash

# Script de démarrage pour environnement Docker (Unix/Linux/Mac)
set -e

echo "🚀 Démarrage de l'environnement Docker FriendsApp..."

# Vérification que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez l'installer avant de continuer."
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez l'installer avant de continuer."
    exit 1
fi

# Copie du fichier d'environnement si nécessaire
if [ ! -f .env ]; then
    echo "📋 Copie du fichier d'environnement..."
    cp .env.docker .env
fi

# Arrêt des services existants
echo "🛑 Arrêt des services existants..."
docker-compose down

# Construction et démarrage des services
echo "🔨 Construction des images Docker..."
if ! docker-compose build --no-cache; then
    echo "❌ Erreur lors de la construction des images Docker."
    exit 1
fi

echo "🔄 Démarrage des services..."
if ! docker-compose up -d; then
    echo "❌ Erreur lors du démarrage des services."
    exit 1
fi

# Attente que les services soient prêts avec vérification
echo "⏳ Attente que les services soient prêts..."
counter=0
while [ $counter -lt 12 ]; do
    sleep 5
    if docker-compose ps | grep -q "Up"; then
        break
    fi
    counter=$((counter + 1))
    echo "⏳ Services en cours de démarrage... ($counter/12)"
done

if [ $counter -eq 12 ]; then
    echo "⚠️ Les services mettent du temps à démarrer. Vérification du statut..."
    docker-compose ps
fi

# Vérification que PHP est accessible
echo "🔍 Vérification du service PHP..."
if ! docker-compose exec -T php php --version &> /dev/null; then
    echo "❌ Le service PHP n'est pas accessible. Vérification des logs..."
    docker-compose logs php
    exit 1
fi

# Installation des dépendances Composer
echo "📦 Installation des dépendances..."
if ! docker-compose exec -T php composer install --optimize-autoloader; then
    echo "⚠️ Erreur lors de l'installation des dépendances Composer."
    echo "Tentative de correction des permissions..."
    docker-compose exec -T php chown -R www-data:www-data /var/www/html
    docker-compose exec -T php composer install --optimize-autoloader
fi

# Mise à jour des dépendances si nécessaire
echo "🔄 Mise à jour des dépendances..."
docker-compose exec -T php composer update symfony/amqp-messenger --with-dependencies --no-interaction

# Exécution des migrations
echo "🗄️ Exécution des migrations de base de données..."
docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Vérification des services
echo "🔍 Vérification des services..."
docker-compose ps

echo ""
echo "✅ Environnement Docker démarré avec succès !"
echo ""
echo "🌐 Application web : http://localhost:8080"
echo "🐰 RabbitMQ Management : http://localhost:15672 (admin/password123)"
echo "🗄️ MySQL : localhost:3306 (app/password)"
echo ""
echo "📝 Commandes utiles :"
echo "  - Arrêter : docker-compose down"
echo "  - Logs : docker-compose logs -f"
echo "  - Console Symfony : docker-compose exec php php bin/console"
echo "  - Tests : docker-compose --profile test up -d && docker-compose exec php php bin/phpunit"
echo ""
echo "🔧 En cas de problème :"
echo "  - Logs PHP : docker-compose logs php"
echo "  - Logs MySQL : docker-compose logs database"
echo "  - Reconstruction : docker-compose build --no-cache && docker-compose up -d"
