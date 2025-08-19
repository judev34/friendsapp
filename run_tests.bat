@echo off
echo 🧪 EXECUTION DES TESTS FONCTIONNELS API EVENTS
echo =============================================
echo.

echo 📋 Preparation de l'environnement de test...
php bin/console cache:clear --env=test
echo.

echo 🔧 Verification de la configuration PHPUnit...
php bin/phpunit --version
echo.

echo 🚀 Execution des tests fonctionnels...
echo.

echo ✅ 1. Tests de base (sans authentification)
php bin/phpunit tests/Functional/EventApiTest.php --verbose --testdox
echo.

echo ✅ 2. Tests avec authentification
php bin/phpunit tests/Functional/EventApiAuthenticatedTest.php --verbose --testdox
echo.

echo ✅ 3. Tests d'integration complets
php bin/phpunit tests/Functional/EventApiIntegrationTest.php --verbose --testdox
echo.

echo 📊 Execution de tous les tests fonctionnels
php bin/phpunit tests/Functional/ --verbose --testdox
echo.

echo 🎉 Tests termines !
pause
