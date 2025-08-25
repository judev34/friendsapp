# 📬 Messenger / RabbitMQ

## DSN par défaut (dev)
```
MESSENGER_TRANSPORT_DSN=amqp://admin:password123@rabbitmq:5672/%2f/messages
```

## Consommer
```bash
docker compose exec -T php php bin/console messenger:consume async -vv
```

## Messages en échec (si Doctrine utilisé)
```bash
docker compose exec -T php php bin/console messenger:failed:show
```

## Astuces
- Vérifier `config/packages/messenger.yaml` (routing, transports)
- En tests, privilégier un transport Doctrine pour éviter la dépendance AMQP
- RabbitMQ UI: http://localhost:15672
