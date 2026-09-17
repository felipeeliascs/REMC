# REMC - Modernizacao da stack (WordPress 7.1 + PHP 8.3 + BuddyPress 14.5.2)
# Uso: .\scripts\remc-modernize.ps1
#
# O estado historico (WordPress 6.4.3 + BuddyPress 12.2.0) continua disponivel
# no commit da reconstrucao. Este script aplica apenas a modernizacao.

$ErrorActionPreference = "Continue"

$dockerBin = "C:\Users\felip\AppData\Local\Programs\DockerDesktop\resources\bin"
if (Test-Path $dockerBin) { $env:Path = "$dockerBin;$env:Path" }

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

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
}

function Invoke-Wp {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]]$WpArgs)
    docker compose run --rm -T wpcli @WpArgs 2>$null | Out-String
}

Write-Host "Baixando imagens modernas..." -ForegroundColor Cyan
docker compose pull wp wpcli
if ($LASTEXITCODE -ne 0) { exit 1 }

Write-Host "Recriando o container do WordPress..." -ForegroundColor Cyan
docker compose up -d --force-recreate wp
if ($LASTEXITCODE -ne 0) { exit 1 }

Write-Host "Aguardando o banco..." -ForegroundColor Cyan
for ($i = 0; $i -lt 60; $i++) {
    Invoke-Wp db query "SELECT 1;" *> $null
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep -Seconds 3
}

Write-Host "Atualizando o banco do WordPress, se necessario..." -ForegroundColor Cyan
Invoke-Wp core update-db

Write-Host "Atualizando o BuddyPress para 14.5.2..." -ForegroundColor Cyan
Invoke-Wp plugin update buddypress --version=14.5.2
if ($LASTEXITCODE -ne 0) {
    Write-Host "Falha ao atualizar o BuddyPress." -ForegroundColor Red
    exit 1
}

Write-Host "Reativando componentes e plugin/tema..." -ForegroundColor Cyan
Invoke-Wp plugin activate buddypress remc-core
Invoke-Wp theme activate remc-educacional
# activity: feed social de dados meteorologicos (remc-core). O restante fica fora do MVP.
foreach ($comp in @("xprofile", "settings", "groups", "activity")) {
    Invoke-Wp bp component activate $comp *> $null
}
foreach ($comp in @("notifications", "friends", "messages", "blogs")) {
    Invoke-Wp bp component deactivate $comp *> $null
}

Write-Host "Aplicando configuracoes..." -ForegroundColor Cyan
Invoke-Wp option update timezone_string "America/Sao_Paulo" *> $null
Invoke-Wp option update blog_public 0 *> $null

Write-Host ""
Write-Host "--- Versoes apos a modernizacao ---" -ForegroundColor Green
$wpVer  = (Invoke-Wp core version | Select-Object -Last 1)
$bpVer  = (Invoke-Wp plugin get buddypress --field=version | Select-Object -Last 1)
$phpVer = (Invoke-Wp eval "echo PHP_VERSION;" | Select-Object -Last 1)
Write-Host ("WordPress   : {0}" -f $wpVer.Trim())
Write-Host ("BuddyPress  : {0}" -f $bpVer.Trim())
Write-Host ("PHP         : {0}" -f $phpVer.Trim())
Write-Host ""
Write-Host "REMC: http://127.0.0.1" -ForegroundColor Green
