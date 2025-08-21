# 🐰 RabbitMQ avec Docker - Guide Multi-Plateforme

## 🚀 Démarrage Rapide

### 1. Lancer RabbitMQ avec Docker
```bash
# Démarrer tous les services (PostgreSQL + RabbitMQ)
docker-compose up -d

# Ou seulement RabbitMQ
docker-compose up -d rabbitmq
```

### 2. Vérifier que RabbitMQ fonctionne
```bash
# Vérifier les logs
docker-compose logs rabbitmq

# Vérifier le status
docker-compose ps
```

### 3. Accéder à l'interface de management
- **URL** : http://localhost:15672
- **Login** : admin
- **Password** : password123

## 🔧 Configuration Symfony

### Variables d'environnement
```bash
# .env.dev
MESSENGER_TRANSPORT_DSN=amqp://admin:password123@127.0.0.1:5672/%2f/messages
```

### Configuration Messenger
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            'App\Message\AsyncEventNotificationMessage': async
```

## 🧪 Test de Fonctionnement

### 1. Démarrer le worker Symfony
```bash
# Dans un terminal séparé
php bin/console messenger:consume async -vv
```

### 2. Créer un événement via API
```bash
# Test avec curl ou Bruno/Postman
POST /api/events
{
    "title": "Test RabbitMQ",
    "description": "Test des notifications asynchrones",
    "startDate": "2025-09-01T14:00:00Z",
    "endDate": "2025-09-01T18:00:00Z",
    "location": "Test Location",
    "maxParticipants": 50,
    "price": "0.00"
}
```

### 3. Observer les résultats
- **API Response** : Immédiate (~100ms)
- **Logs Worker** : Messages traités en arrière-plan
- **Interface RabbitMQ** : Queues et messages visibles

## 📊 Monitoring

### Interface RabbitMQ
- **Queues** : Voir les messages en attente
- **Connections** : Connexions Symfony actives  
- **Exchanges** : Routage des messages

### Commandes Symfony
```bash
# Statistiques des queues
php bin/console messenger:stats

# Consommer avec debug
php bin/console messenger:consume async -vv

# Setup des transports
php bin/console messenger:setup-transports
```

## 🔄 Coexistence avec EventListeners

Le système actuel fonctionne avec **DEUX systèmes en parallèle** :

1. **EventListeners** (synchrones) - Existant
   - Notifications immédiates
   - Bloque la réponse HTTP
   - Préfixe : `[SYNC]`

2. **Messenger** (asynchrones) - Nouveau  
   - Notifications en arrière-plan
   - Réponse HTTP immédiate
   - Préfixe : `[ASYNC]`

## 🌍 Compatibilité Multi-Plateforme

### Windows
```powershell
docker-compose up -d
```

### Mac/Linux
```bash
docker-compose up -d
```

### Arrêt
```bash
# Arrêter les services
docker-compose down

# Arrêter et supprimer les volumes
docker-compose down -v
```

## 🚨 Troubleshooting

### RabbitMQ ne démarre pas
```bash
# Vérifier les ports
netstat -an | findstr 5672
netstat -an | findstr 15672

# Redémarrer le service
docker-compose restart rabbitmq
```

### Messages non traités
```bash
# Vérifier la configuration
php bin/console debug:messenger

# Vérifier les transports
php bin/console messenger:stats
```

### Connexion refusée
- Vérifier que Docker est démarré
- Vérifier les credentials dans `.env.dev`
- Vérifier les ports dans `compose.yaml`
