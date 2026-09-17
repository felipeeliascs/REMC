# REMC - Inicializacao do ambiente Docker (WordPress 6.4.3 + BuddyPress 12.2.0)
# Uso: .\scripts\remc-init.ps1
# Requer: Docker Desktop em execucao

$ErrorActionPreference = "Continue"

# Garante o docker no PATH (instalacao em escopo de usuario)
$dockerBin = "C:\Users\felip\AppData\Local\Programs\DockerDesktop\resources\bin"
if (Test-Path $dockerBin) { $env:Path = "$dockerBin;$env:Path" }

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

# Carrega variaveis do .env (se existirem)
$envFile = Join-Path $root ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith("#") -and $line.Contains("=")) {
            $idx = $line.IndexOf("=")
            $key = $line.Substring(0, $idx).Trim()
            $val = $line.Substring($idx + 1).Trim().Trim("'").Trim('"')
            if ($key) { Set-Item -Path "Env:$key" -Value $val -ErrorAction SilentlyContinue }
        }
    }
    Write-Host "Variaveis do .env carregadas." -ForegroundColor DarkGray
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "ERRO: comando 'docker' nao encontrado." -ForegroundColor Red
    exit 1
}

function Invoke-Wp {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]]$WpArgs)
    docker compose run --rm -T wpcli @WpArgs 2>$null | Out-String
}

Write-Host "Verificando daemon do Docker..." -ForegroundColor Cyan
docker info *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERRO: o Docker Desktop nao esta em execucao. Abra-o e aguarde 'Engine running'." -ForegroundColor Red
    exit 1
}

Write-Host "Subindo containers (mariadb, wp, wpcli)..." -ForegroundColor Cyan
docker compose up -d mariadb wp
if ($LASTEXITCODE -ne 0) { exit 1 }

Write-Host "Aguardando o banco de dados..." -ForegroundColor Cyan
$ready = $false
for ($i = 0; $i -lt 60; $i++) {
    Invoke-Wp db query "SELECT 1;" *> $null
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Start-Sleep -Seconds 3
}
if (-not $ready) {
    Write-Host "ERRO: banco de dados nao respondeu a tempo." -ForegroundColor Red
    exit 1
}
Write-Host "Banco de dados pronto." -ForegroundColor Green

Write-Host "Aguardando o WordPress gerar wp-config.php..." -ForegroundColor Cyan
for ($i = 0; $i -lt 60; $i++) {
    Invoke-Wp core version *> $null
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep -Seconds 2
}

$siteUrl   = if ($env:WP_SITEURL) { $env:WP_SITEURL } else { "http://127.0.0.1" }
$adminUser = if ($env:WP_ADMIN_USER) { $env:WP_ADMIN_USER } else { "admin" }
$adminPass = if ($env:WP_ADMIN_PASSWORD) { $env:WP_ADMIN_PASSWORD } else { "admin_password_123" }
$adminMail = if ($env:WP_ADMIN_EMAIL) { $env:WP_ADMIN_EMAIL } else { "admin@localhost" }

Invoke-Wp core is-installed *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Instalando WordPress..." -ForegroundColor Cyan
    Invoke-Wp core install --url="$siteUrl" --title="REMC - Rede Educacional de Monitoramento Climatico" --admin_user="$adminUser" --admin_password="$adminPass" --admin_email="$adminMail" --skip-email
    if ($LASTEXITCODE -ne 0) { exit 1 }
    Write-Host "WordPress instalado." -ForegroundColor Green
} else {
    Write-Host "WordPress ja instalado." -ForegroundColor Green
}

Write-Host "Instalando/ativando BuddyPress 12.2.0..." -ForegroundColor Cyan
Invoke-Wp plugin is-installed buddypress *> $null
if ($LASTEXITCODE -ne 0) {
    Invoke-Wp plugin install buddypress --version=12.2.0 --activate
    if ($LASTEXITCODE -ne 0) { exit 1 }
} else {
    Invoke-Wp plugin activate buddypress *> $null
}

Write-Host "Ativando componentes do BuddyPress (xprofile, settings, groups)..." -ForegroundColor Cyan
foreach ($comp in @("xprofile", "settings", "groups")) {
    Invoke-Wp bp component activate $comp *> $null
}
# Garante que componentes nao usados no MVP permanecam desativados
foreach ($comp in @("activity", "friends", "messages", "blogs", "notifications")) {
    Invoke-Wp bp component deactivate $comp *> $null
}

Write-Host "Ativando plugin e tema da REMC..." -ForegroundColor Cyan
Invoke-Wp plugin activate remc-core
Invoke-Wp theme activate remc-educacional

Write-Host "Aplicando configuracoes (pt_BR, fuso, restricoes)..." -ForegroundColor Cyan
Invoke-Wp language core install pt_BR --activate *> $null
Invoke-Wp option update timezone_string "America/Sao_Paulo" *> $null
Invoke-Wp option update blog_public 0 *> $null
Invoke-Wp option update blogname "REMC - Rede Educacional de Monitoramento Climatico" *> $null
Invoke-Wp option update WPLANG "pt_BR" *> $null

Write-Host "Executando bootstrap de dados ficticios..." -ForegroundColor Cyan
Invoke-Wp remc bootstrap

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host " REMC pronta: $siteUrl" -ForegroundColor Green
Write-Host " Admin: $adminUser / $adminPass" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
