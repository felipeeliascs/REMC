# Scripts - Build e Deployment REMC

## Scripts Disponíveis

### setup.sh
Script bash para configurar o ambiente dentro do contêiner WordPress.

**Uso:**
```bash
docker-compose up -d
docker-compose exec wp bash
# Dentro do contêiner:
./scripts/setup.sh
```

**O que faz:**
- Aguarda banco de dados estar pronto
- Instala WordPress (se não instalado)
- Ativa plugin `remc-core`
- Ativa tema `remc-educacional`
- Define timezone (`America/Sao_Paulo`)
- Define idioma (`pt_BR`)
- Executa bootstrap se `REMC_BOOTSTRAP=true`

### bootstrap.ps1
Script PowerShell para criar dados de demonstração no Windows.

**Uso:**
```powershell
.\scripts\bootstrap.ps1
```

**O que faz:**
- Cria escola "Escola Exemplo"
- Cria administrador (`admin`)
- Cria professor (`professor_exemplo`)
- Cria 3 alunos (`aluno_joao`, `aluno_maria`, `aluno_pedro`)
- Cria 2 turmas (Turma A, Turma B)
- Linka alunos às turmas
- Cria 4 pontos de observação
- Cria 4 observações de exemplo
- Cria 13 tutoriais (I01-I03, E01-E03, R01-R07)
- Cria 2 atividades de exemplo

### wp-config.php
Configuração do WordPress (gerada via `.env.example`).

**Variáveis do `.env`:**
- `WP_DB_NAME`, `WP_DB_USER`, `WP_DB_PASSWORD`, `WP_DB_HOST`
- `WP_TABLE_PREFIX`
- `WP_ADMIN_USER`, `WP_ADMIN_PASSWORD`, `WP_ADMIN_EMAIL`
- `WP_SITEURL`, `WP_HOME`
- Chaves de segurança (AUTH_KEY, SECURE_AUTH_KEY, etc.)
- `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY`

**Exemplo de uso:**
```bash
# Copiar .env.example para .env
cp .env.example .env

# Gerar chaves de segurança
# Acesse https://api.wordpress.org/secret-key/1.1/salt/
# e cole no .env

# Iniciar ambiente
docker-compose up -d
```

## Docker Compose

### Arquivo: compose.yaml
Configuração dos contêineres:

| Serviço | Imagem | Portas | Volumes |
|---------|--------|--------|---------|
| mariadb | mariadb:10.6.16 | 3306 (interno) | mariadb_data:/var/lib/mysql |
| wp | wordpress:6.4.3-php8.1 | 80:80 | ./wp-content:/var/www/html/wp-content |

**Comandos:**
```bash
# Iniciar ambiente
docker-compose up -d

# Parar ambiente
docker-compose down

# Ver logs
docker-compose logs -f

# Executar comando dentro do contêiner
docker-compose exec wp bash
docker-compose exec wp wp core version
```

## WordPress CLI

### Comandos Principais

```bash
# Verificar versão
wp core version --allow-root

# Verificar instalação
wp core is-installed --allow-root

# Instalar WordPress
wp core install \
  --url=http://127.0.0.1 \
  --title="REMC" \
  --admin_user=admin \
  --admin_password=senha123 \
  --admin_email=admin@localhost \
  --allow-root

# Ativar plugin
wp plugin activate remc-core --allow-root

# Ativar tema
wp theme activate remc-educacional --allow-root

# Executar bootstrap
wp remc bootstrap --allow-root

# Ver usuários
wp user list --allow-root

# Ver post types
wp post type list --allow-root
```

## Backup e Restore

### Backup do Banco
```bash
docker exec remc-mariadb mysqldump -u remc_user -premc_pass remc_db > backup.sql
```

### Restore do Banco
```bash
docker exec -i remc-mariadb mysql -u remc_user -premc_pass remc_db < backup.sql
```

### Backup dos Dados
```bash
# Copiar wp-content para backup
docker cp remc-wp:/var/www/html/wp-content ./backup-wp-content
```

## Debugging

### Ver logs do PHP
```bash
docker-compose exec wp tail -f /var/log/apache2/error.log
```

### Ver logs do WordPress
```bash
docker-compose exec wp wp db query "SELECT * FROM wp_options WHERE option_name LIKE '%debug%'" --allow-root
```

### Testar conexão com banco
```bash
docker-compose exec wp wp db check --allow-root
```

## Limpeza

### Reset completo (cuidado!)
```bash
docker-compose down -v
docker volume rm remc_mariadb_data
docker-compose up -d
```

### Resetar banco apenas
```bash
docker exec remc-mariadb mysql -u root -prootpass -e "DROP DATABASE IF EXISTS remc_db; CREATE DATABASE remc_db;"
```

## CI/CD (Futuro)

Para implantação em produção:
1. Atualizar versões de WordPress, BuddyPress, PHP
2. Revalidar compatibilidade
3. Testar em staging
4. Realizar backup antes de atualizações
5. Deploy via Git ou FTP

**Importante:** Este ambiente mantém versões fixas para reprodução histórica. Em produção, atualizar regularmente e testar cuidadosamente.
