# Setup REMC Environment

#!/usr/bin/env bash

# Script para configurar o ambiente REMC
# Execute este script dentro do contêiner remc-wp

set -e

echo "Configurando ambiente REMC..."

# Wait for database
echo "Aguardando banco de dados..."
until wp db check >/dev/null 2>&1; do
  sleep 2
done

echo "Banco de dados pronto."

# Install WordPress (se não instalado)
if ! wp core is-installed --allow-root; then
  echo "Instalando WordPress..."
  
  DB_NAME="${WP_DB_NAME:-remc_db}"
  DB_USER="${WP_DB_USER:-remc_user}"
  DB_PASSWORD="${WP_DB_PASSWORD:-remc_pass}"
  DB_HOST="${WP_DB_HOST:-remc-mariadb:3306}"
  TABLE_PREFIX="${WP_TABLE_PREFIX:-remc_}"

  # Senha do administrador: usa WP_ADMIN_PASSWORD ou gera uma aleatoria.
  ADMIN_PASS_GERADA=""
  if [ -z "${WP_ADMIN_PASSWORD}" ]; then
    ADMIN_PASS_GERADA="$(head -c 24 /dev/urandom | base64 | tr -d '/+=' | cut -c1-20)"
    WP_ADMIN_PASSWORD="${ADMIN_PASS_GERADA}"
  fi

  wp core install \
    --url=http://127.0.0.1 \
    --title="REMC - Rede Educacional de Monitoramento Climático" \
    --admin_user="${WP_ADMIN_USER:-admin}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL:-admin@localhost}" \
    --allow-root \
    --dbhost="${DB_HOST}" \
    --dbuser="${DB_USER}" \
    --dbpass="${DB_PASSWORD}" \
    --dbname="${DB_NAME}" \
    --table_prefix="${TABLE_PREFIX}"

  echo "WordPress instalado."
  if [ -n "${ADMIN_PASS_GERADA}" ]; then
    echo "Senha do admin (gerada agora, anote): ${ADMIN_PASS_GERADA}"
    echo "Para fixar, defina WP_ADMIN_PASSWORD no .env."
  fi
else
  echo "WordPress já instalado."
fi

# Activate plugins
echo "Ativando plugins..."
wp plugin activate remc-core --allow-root
echo "Plugin remc-core ativado."

# Set timezone and language
wp option update timezone_string America/Sao_Paulo --allow-root
wp option update blog_public 0 --allow-root
wp language core install pt_BR --allow-root
wp language core activate pt_BR --allow-root

echo "Configurações de idioma e fuso atualizadas."

# Run bootstrap (se solicitado)
if [ "${REMC_BOOTSTRAP:-false}" = "true" ]; then
  echo "Executando bootstrap..."
  wp remc bootstrap --allow-root
  echo "Bootstrap concluído."
fi

# Show summary
echo ""
echo "=========================================="
echo "Ambiente REMC configurado!"
echo "=========================================="
echo "URL: http://127.0.0.1"
echo "Admin: ${WP_ADMIN_USER:-admin}"
echo "=========================================="
