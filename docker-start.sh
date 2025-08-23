#!/bin/bash

# Script de démarrage pour environnement Docker (Unix/Linux/Mac)
set -e

echo "🚀 Démarrage de l'environnement Docker FriendsApp..."

# Vérification que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez l'installer avant de continuer."
    exit 1
fi

# Détection de la commande Docker Compose (v2 ou v1)
if docker compose version &> /dev/null; then
    DC="docker compose"
elif command -v docker-compose &> /dev/null; then
    DC="docker-compose"
else
    echo "❌ Docker Compose n'est pas installé. Veuillez l'installer avant de continuer."
    exit 1
fi

# Copie du fichier d'environnement si nécessaire
if [ ! -f .env ]; then
    echo "📋 Copie du fichier d'environnement..."
    if [ -f .env.docker ]; then
        cp .env.docker .env
    elif [ -f .env.docker.example ]; then
        cp .env.docker.example .env
    else
        echo "❌ Fichier .env.docker(.example) introuvable."
        exit 1
    fi
fi

# Arrêt des services existants
echo "🛑 Arrêt des services existants..."
$DC down

# Construction et démarrage des services
echo "🔨 Construction des images Docker..."
if ! $DC build --no-cache; then
    echo "❌ Erreur lors de la construction des images Docker."
    exit 1
fi

echo "🔄 Démarrage des services..."
if ! $DC up -d; then
    echo "❌ Erreur lors du démarrage des services."
    exit 1
fi

# Attente que les services soient prêts avec vérification
echo "⏳ Attente que les services soient prêts..."
counter=0
while [ $counter -lt 12 ]; do
    sleep 5
    if $DC ps | grep -q "Up"; then
        break
    fi
    counter=$((counter + 1))
    echo "⏳ Services en cours de démarrage... ($counter/12)"
done

if [ $counter -eq 12 ]; then
    echo "⚠️ Les services mettent du temps à démarrer. Vérification du statut..."
    $DC ps
fi

# Vérification que PHP est accessible
echo "🔍 Vérification du service PHP..."
if ! $DC exec -T php php --version &> /dev/null; then
    echo "❌ Le service PHP n'est pas accessible. Vérification des logs..."
    $DC logs php
    exit 1
fi

# Installation des dépendances Composer
echo "📦 Installation des dépendances..."
if ! $DC exec -T php composer install --optimize-autoloader; then
    echo "⚠️ Erreur lors de l'installation des dépendances Composer."
    echo "Tentative de correction des permissions..."
    $DC exec -T php chown -R www-data:www-data /var/www/html
    $DC exec -T php composer install --optimize-autoloader
fi

# (Optionnel) Mise à jour ciblée — désactivée par défaut pour garder la reproductibilité
# if [ "${FORCE_UPDATE_AMQP:-0}" = "1" ]; then
#   echo "🔄 Mise à jour symfony/amqp-messenger (à la demande)..."
#   $DC exec -T php composer update symfony/amqp-messenger --with-dependencies --no-interaction
# fi

# Exécution des migrations
echo "🗄️ Exécution des migrations de base de données..."
$DC exec -T php php bin/console doctrine:database:create --if-not-exists
$DC exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Vérification des services
echo "🔍 Vérification des services..."
$DC ps

echo ""
echo "✅ Environnement Docker démarré avec succès !"
echo ""
echo "🌐 Application web : http://localhost:8080"
echo "🐰 RabbitMQ Management : http://localhost:15672 (admin/password123)"
echo "🗄️ MySQL : localhost:3306 (app/password)"
echo ""
echo "📝 Commandes utiles :"
echo "  - Arrêter : $DC down"
echo "  - Logs : $DC logs -f"
echo "  - Console Symfony : $DC exec php php bin/console"
echo "  - Tests : $DC --profile test up -d && $DC exec -T php-test sh -lc 'APP_ENV=test DATABASE_URL=\"mysql://app:password@database-test:3306/friendsapp_test\" php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.dist.xml'"
echo ""
echo "🔧 En cas de problème :"
echo "  - Logs PHP : $DC logs php"
echo "  - Logs MySQL : $DC logs database"
echo "  - Reconstruction : $DC build --no-cache && $DC up -d"
