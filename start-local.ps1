# Iniciar REMC com PHP Built-in Server
# Executar: .\start-local.ps1

Write-Host "=== REMC - Iniciar Servidor Local ===" -ForegroundColor Green

# Configurações
$host = "127.0.0.1"
$port = 8000
$wpDir = "wp-core"

# Verificar se PHP está instalado
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "Erro: PHP não encontrado no PATH" -ForegroundColor Red
    Write-Host "Instale o PHP ou use Docker Compose" -ForegroundColor Yellow
    exit 1
}

Write-Host "Verificando diretório WordPress..."

# Verificar se wp-core existe
if (-not (Test-Path $wpDir)) {
    Write-Host "wp-core não encontrado. Criando..." -ForegroundColor Yellow
    
    # Baixar WordPress 6.4.3
    $wpZip = "wordpress-6.4.3.zip"
    $wpUrl = "https://wordpress.org/wordpress-6.4.3.zip"
    
    Write-Host "Baixando WordPress 6.4.3..." -ForegroundColor Cyan
    try {
        Invoke-WebRequest -Uri $wpUrl -OutFile $wpZip
        Expand-Archive $wpZip -DestinationPath .
        Move-Item wordpress $wpDir -Force
        Remove-Item $wpZip
        Write-Host "WordPress instalado em $wpDir" -ForegroundColor Green
    } catch {
        Write-Host "Erro ao baixar WordPress: $_" -ForegroundColor Red
        exit 1
    }
}

# Verificar se plugin existe
if (-not (Test-Path "$wpDir\wp-content\plugins\remc-core")) {
    Write-Host "Copiando plugin REMC Core..." -ForegroundColor Cyan
    Copy-Item "wp-content\plugins\remc-core" "$wpDir\wp-content\plugins\" -Recurse
    Write-Host "Plugin copiado" -ForegroundColor Green
}

# Verificar se tema existe
if (-not (Test-Path "$wpDir\wp-content\themes\remc-educacional")) {
    Write-Host "Copiando tema REMC Educacional..." -ForegroundColor Cyan
    Copy-Item "wp-content\themes\remc-educacional" "$wpDir\wp-content\themes\" -Recurse
    Write-Host "Tema copiado" -ForegroundColor Green
}

# Verificar se wp-config.php existe
$wpConfig = "$wpDir\wp-config.php"
if (-not (Test-Path $wpConfig)) {
    Write-Host "Criando wp-config.php..." -ForegroundColor Yellow
    
    $dbHost = "localhost"
    $dbName = "remc_db"
    $dbUser = "remc_user"
    $dbPass = "remc_pass"
    
    $configContent = @'
<?php
define( 'DB_NAME', '$dbName' );
define( 'DB_USER', '$dbUser' );
define( 'DB_PASSWORD', '$dbPass' );
define( 'DB_HOST', '$dbHost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'remc_';

define( 'WP_DEBUG', true );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
'@
    
    Set-Content -Path $wpConfig -Value $configContent -Encoding UTF8
    Write-Host "wp-config.php criado" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host ("Iniciando servidor em http://{0}:{1}" -f $host, $port) -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Primeiro acesso:" -ForegroundColor Cyan
Write-Host "  1. Acesse http://$host:$port" -ForegroundColor White
Write-Host "  2. Crie um usuário administrador" -ForegroundColor White
Write-Host "  3. Ative o plugin REMC Core" -ForegroundColor White
Write-Host "  4. Ative o tema REMC Educacional" -ForegroundColor White
Write-Host ""
Write-Host "Comandos úteis:" -ForegroundColor Cyan
Write-Host ("  wp core install --url=http://{0}:{1} --title=REMC --admin_user=admin --admin_password=`"`$WP_ADMIN_PASSWORD`"" -f $host, $port) -ForegroundColor White
Write-Host "  wp plugin activate remc-core" -ForegroundColor White
Write-Host "  wp theme activate remc-educacional" -ForegroundColor White
Write-Host "  wp remc bootstrap" -ForegroundColor White
Write-Host ""

# Iniciar servidor PHP built-in
php -S "${host}:${port}" -t $wpDir
