# Iniciar REMC Localmente

## Requisitos

### Opção 1: Docker (Recomendado)

```bash
docker-compose up -d
```

### Opção 2: Servidor PHP Local

Se você tem PHP instalado:
```bash
php -S 127.0.0.1:8000 -t wp-core
```

### Opção 3: XAMPP/WAMP/Laragon

1. Copiar `wp-core` para `C:\xampp\htdocs\remc\`
2. Criar banco de dados no phpMyAdmin
3. Acessar http://localhost/remc

## Criar Banco de Dados (MySQL/MariaDB)

```sql
CREATE DATABASE remc_db;
CREATE USER 'remc_user'@'localhost' IDENTIFIED BY 'remc_pass';
GRANT ALL PRIVILEGES ON remc_db.* TO 'remc_user'@'localhost';
FLUSH PRIVILEGES;
```

## Acessar WordPress

- Site: http://127.0.0.1:8000
- Admin: http://127.0.0.1:8000/wp-admin

## Comandos WP-CLI (se disponível)

```bash
wp core install --url=http://127.0.0.1:8000 --title=REMC --admin_user=admin --admin_password=senha123
wp plugin activate remc-core
wp theme activate remc-educacional
wp remc bootstrap
```
