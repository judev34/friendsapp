@echo off
setlocal enabledelayedexpansion

REM Script de démarrage pour environnement Docker (Windows)

echo 🚀 Démarrage de l'environnement Docker FriendsApp...

REM Vérification que Docker est installé
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker n'est pas installé. Veuillez l'installer avant de continuer.
    pause
    exit /b 1
)

docker-compose --version >nul 2>&1
if errorlevel 1 (
    docker compose version >nul 2>&1
    if errorlevel 1 (
        echo ❌ Docker Compose n'est pas installé. Veuillez l'installer avant de continuer.
        pause
        exit /b 1
    )
)

REM Copie du fichier d'environnement si nécessaire
if not exist .env (
    echo 📋 Copie du fichier d'environnement...
    copy .env.docker .env
)

REM Arrêt des services existants
echo 🛑 Arrêt des services existants...
docker-compose down

REM Construction et démarrage des services
echo 🔨 Construction des images Docker...
docker-compose build --no-cache
if errorlevel 1 (
    echo ❌ Erreur lors de la construction des images Docker.
    pause
    exit /b 1
)

echo 🔄 Démarrage des services...
docker-compose up -d
if errorlevel 1 (
    echo ❌ Erreur lors du démarrage des services.
    pause
    exit /b 1
)

REM Attente que les services soient prêts avec vérification
echo ⏳ Attente que les services soient prêts...
set /a counter=0
:wait_loop
timeout /t 5 /nobreak >nul
docker-compose ps | findstr "Up" >nul
if errorlevel 1 (
    set /a counter+=1
    if !counter! lss 12 (
        echo ⏳ Services en cours de démarrage... (!counter!/12)
        goto wait_loop
    ) else (
        echo ⚠️ Les services mettent du temps à démarrer. Vérification du statut...
        docker-compose ps
    )
)

REM Vérification que PHP est accessible
echo 🔍 Vérification du service PHP...
docker-compose exec -T php php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Le service PHP n'est pas accessible. Vérification des logs...
    docker-compose logs php
    pause
    exit /b 1
)

REM Copie du fichier d'environnement dans le container
echo 📋 Configuration de l'environnement dans le container...
docker-compose exec -T php cp .env.docker .env

REM Installation des dépendances Composer
echo 📦 Installation des dépendances...
docker-compose exec -T php composer install --optimize-autoloader
if errorlevel 1 (
    echo ⚠️ Erreur lors de l'installation des dépendances Composer.
    echo Tentative de correction des permissions...
    docker-compose exec -T php chown -R www-data:www-data /var/www/html
    docker-compose exec -T php composer install --optimize-autoloader
)

REM Exécution des migrations
echo 🗄️ Exécution des migrations de base de données...
docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

REM Vérification des services
echo 🔍 Vérification des services...
docker-compose ps

echo.
echo ✅ Environnement Docker démarré avec succès !
echo.
echo 🌐 Application web : http://localhost:8080
echo 🐰 RabbitMQ Management : http://localhost:15672 (admin/password123)
echo 🗄️ MySQL : localhost:3306 (app/password)
echo.
echo 📝 Commandes utiles :
echo   - Arrêter : docker-compose down
echo   - Logs : docker-compose logs -f
echo   - Console Symfony : docker-compose exec php php bin/console
echo   - Tests : docker-compose --profile test up -d ^&^& docker-compose exec php php bin/phpunit
echo.
echo 🔧 En cas de problème :
echo   - Logs PHP : docker-compose logs php
echo   - Logs MySQL : docker-compose logs database
echo   - Reconstruction : docker-compose build --no-cache ^&^& docker-compose up -d
echo.
pause
