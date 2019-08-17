# lock and unlock
Script criado para bloquear e desbloquear os repositórios do hacknit

## bootstrap

```bash
cp .env.develop .env
docker-compose build
docker-compose up -d
```

Edite o arquivo .env e coloque o token de acesso

## lock
```bash
docker-compose exec php7 index.php lock
```

## unlock
```bash
docker-compose exec php7 index.php unlock
```