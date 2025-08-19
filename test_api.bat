@echo off
echo ========================================
echo    EventApp - Tests API Automatises
echo ========================================
echo.

set BASE_URL=http://localhost:8000
set EMAIL=test@example.com
set PASSWORD=motdepasse123

echo [1/4] Test d'inscription utilisateur...
curl -X POST %BASE_URL%/api/register ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\",\"firstName\":\"Jean\",\"lastName\":\"Dupont\"}" ^
  -w "\nStatus: %%{http_code}\n" ^
  -s
echo.

echo [2/4] Test de connexion...
curl -X POST %BASE_URL%/api/login ^
  -H "Content-Type: application/json" ^
  -c cookies.txt ^
  -d "{\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\"}" ^
  -w "\nStatus: %%{http_code}\n" ^
  -s
echo.

echo [3/4] Test recuperation profil...
curl -X GET %BASE_URL%/api/me ^
  -H "Content-Type: application/json" ^
  -b cookies.txt ^
  -w "\nStatus: %%{http_code}\n" ^
  -s
echo.

echo [4/4] Test deconnexion...
curl -X POST %BASE_URL%/api/logout ^
  -H "Content-Type: application/json" ^
  -b cookies.txt ^
  -w "\nStatus: %%{http_code}\n" ^
  -s
echo.

echo ========================================
echo Tests termines !
echo ========================================
pause
