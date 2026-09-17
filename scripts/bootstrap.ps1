# Bootstrap REMC Data

#!/usr/bin/env bash

# Script PowerShell para criar dados de demonstração na REMC
# Execute: .\scripts\bootstrap.ps1

Write-Host "Iniciando bootstrap da REMC..." -ForegroundColor Green

# Configurações
$wordpressUrl = "http://127.0.0.1"
$wpCli = "wp"

# Verifica se WP-CLI está disponível
if (-not (Get-Command $wpCli -ErrorAction SilentlyContinue)) {
    Write-Host "WP-CLI não encontrado. Instale em https://wp-cli.org/" -ForegroundColor Red
    exit 1
}

# Função auxiliar
function Run-WpCommand {
    param($command)
    Write-Host "> wp $command" -ForegroundColor Cyan
    & $wpCli $command --allow-root
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Erro ao executar: wp $command" -ForegroundColor Red
        exit 1
    }
}

# 1. Criar escola
Write-Host "Criando escola..." -ForegroundColor Yellow
$escolaId = & $wpCli post create `
    --post_type=remc_escola `
    --post_title="Escola Exemplo" `
    --post_status=publish `
    --allow-root `
    --format=json

if (-not $escolaId) {
    Write-Host "Erro ao criar escola" -ForegroundColor Red
    exit 1
}
Write-Host "Escola criada: $escolaId" -ForegroundColor Green

# 2. Criar usuário administrador
Write-Host "Criando administrador..." -ForegroundColor Yellow
$adminUser = & $wpCli user get admin --allow-root --format=json
if (-not $adminUser) {
    & $wpCli user create admin admin@localhost --role=administrator --allow-root
    Write-Host "Administrador criado: admin" -ForegroundColor Green
} else {
    Write-Host "Administrador já existe: admin" -ForegroundColor Green
}

# 3. Criar professor
Write-Host "Criando professor..." -ForegroundColor Yellow
$professorUser = & $wpCli user get professor_exemplo --allow-root --format=json
if (-not $professorUser) {
    & $wpCli user create professor_exemplo professor@localhost --role=professor --allow-root
    & $wpCli user meta update professor_exemplo _linked_escola $escolaId --allow-root
    Write-Host "Professor criado: professor_exemplo" -ForegroundColor Green
} else {
    Write-Host "Professor já existe: professor_exemplo" -ForegroundColor Green
}

# 4. Criar alunos
Write-Host "Criando alunos..." -ForegroundColor Yellow
$alunos = @(
    @{username="aluno_joao"; email="joao@remc.local"; display="João da Silva"}
    @{username="aluno_maria"; email="maria@remc.local"; display="Maria Oliveira"}
    @{username="aluno_pedro"; email="pedro@remc.local"; display="Pedro Santos"}
)

foreach ($aluno in $alunos) {
    $user = & $wpCli user get $aluno.username --allow-root --format=json
    if (-not $user) {
        & $wpCli user create $aluno.username $aluno.email --role=aluno --allow-root
        & $wpCli user meta update $aluno.username display_name $aluno.display --allow-root
        Write-Host "Aluno criado: $($aluno.username)" -ForegroundColor Green
    } else {
        Write-Host "Aluno já existe: $($aluno.username)" -ForegroundColor Green
    }
}

# 5. Criar turmas (BuddyPress groups)
Write-Host "Criando turmas..." -ForegroundColor Yellow
$turmas = @(
    @{title="Turma A - 5º Ano"; desc="Turma do 5º ano com foco em ciências"}
    @{title="Turma B - 6º Ano"; desc="Turma do 6º ano com foco em meio ambiente"}
)

foreach ($turma in $turmas) {
    $groupId = & $wpCli bp group get $turma.title --allow-root --format=json
    if (-not $groupId) {
        & $wpCli bp group create `
            --name=$turma.title `
            --description=$turma.desc `
            --creator=professor_exemplo `
            --hide-sitewide=1 `
            --allow-root
        
        # Link professor to group
        & $wpCli bp group add-member $turma.title professor_exemplo --allow-root
        & $wpCli bp group promote $turma.title professor_exemplo admin --allow-root
        & $wpCli bp group update-meta $turma.title professor_responsavel professor_exemplo --allow-root
        
        Write-Host "Turma criada: $($turma.title)" -ForegroundColor Green
    } else {
        Write-Host "Turma já existe: $($turma.title)" -ForegroundColor Green
    }
}

# 6. Criar pontos de observação
Write-Host "Criando pontos de observação..." -ForegroundColor Yellow
$locals = @(
    @{title="Ponto Escola"; tipo="escola"}
    @{title="Ponto Casa"; tipo="casa"}
)

foreach ($turma in $turmas) {
    foreach ($local in $locals) {
        $postId = & $wpCli post create `
            --post_type=remc_local `
            --post_title="$($local.title) - $($turma.title)" `
            --post_status=publish `
            --allow-root `
            --format=json
        
        & $wpCli post meta set $postId _turma $turma.title --allow-root
        & $wpCli post meta set $postId _tipo $local.tipo --allow-root
        & $wpCli post meta set $postId _pluviometer "pluviometro_ref_001" --allow-root
        & $wpCli post meta set $postId _pluviometer_version "1.0" --allow-root
        
        Write-Host "Local criado: $($local.title) para $($turma.title)" -ForegroundColor Green
    }
}

# 7. Criar observações de exemplo
Write-Host "Criando observações..." -ForegroundColor Yellow
$obsDate = Get-Date.AddDays(-7).ToString("yyyy-MM-dd HH:mm:ss")

$observations = @(
    @{title="Observação Pluviométrica - Chuva"; status="aprovado"; precip="12.5"; local="Ponto Escola"}
    @{title="Observação Pluviométrica - Sem Chuva"; status="aprovado"; precip="0"; local="Ponto Casa"}
    @{title="Observação Anemômetro"; status="pendente"; rpm="30"; local="Ponto Escola"}
    @{title="Observação Barômetro"; status="aprovado"; baro="-2.5"; local="Ponto Casa"}
)

foreach ($obs in $observations) {
    $postId = & $wpCli post create `
        --post_type=remc_observacao `
        --post_title=$obs.title `
        --post_author=aluno_joao `
        --post_status=$obs.status `
        --post_date=$obsDate `
        --allow-root `
        --format=json
    
    & $wpCli post meta set $postId _observation_date $obsDate --allow-root
    & $wpCli post meta set $postId _status $obs.status --allow-root
    & $wpCli post meta set $postId _turma $turmas[0].title --allow-root
    & $wpCli post meta set $postId _local_id "1" --allow-root
    & $wpCli post meta set $postId _precipitation $obs.precip --allow-root
    & $wpCli post meta set $postId _instrument "pluviometro_ref_001" --allow-root
    
    Write-Host "Observação criada: $($obs.title)" -ForegroundColor Green
}

# 8. Criar tutoriais (13 guias)
Write-Host "Criando tutoriais..." -ForegroundColor Yellow

$docsDir = Join-Path $PSScriptRoot "..\docs"
$catalogFile = Join-Path $docsDir "catalogo-de-atividades.md"

if (Test-Path $catalogFile) {
    Write-Host "Catálogo de atividades encontrado em $catalogFile" -ForegroundColor Green
} else {
    Write-Host "Criando catálogo de atividades..." -ForegroundColor Yellow
    
    $tutorials = @(
        @{id="I01"; title="Pluviômetro de Garrafa PET"; cat="instrumentos"; obj="Construir e utilizar um pluviômetro caseiro"; mat="Garrafa PET, faca, fita adesiva, régua"}
        @{id="I02"; title="Anemômetro de Copos"; cat="instrumentos"; obj="Construir anemômetro para medir rotação"; mat="4 copos de papel, canudos, alfinete"}
        @{id="I03"; title="Barômetro de Bexiga"; cat="instrumentos"; obj="Construir barômetro para observar variações de pressão"; mat="Bexiga, frasco de vidro, canudo"}
        @{id="E01"; title="Nuvem na Garrafa"; cat="experimentos"; obj="Investigar condensação e formação de nuvens"; mat="Garrafa PET, água morna, fósforo"}
        @{id="E02"; title="Mini Ciclo da Água"; cat="experimentos"; obj="Observar evaporação e condensação"; mat="Pote de vidro, água, tapa-jantares"}
        @{id="E03"; title="Experimento das Duas Vasilhas"; cat="experimentos"; obj="Comparar temperaturas com e sem cobertura"; mat="2 vasilhas, água, cobertores"}
        @{id="R01"; title="Registro Pluviométrico Diário"; cat="rotinas"; obj="Realizar leitura diária do pluviômetro"; mat="Pluviômetro, formulário"}
        @{id="R02"; title="Monitoramento da Pressão"; cat="rotinas"; obj="Acompanhar variações de pressão"; mat="Barômetro de bexiga"}
        @{id="R03"; title="Contagem RPM do Vento"; cat="rotinas"; obj="Contar rotações do anemômetro"; mat="Anemômetro, cronômetro"}
        @{id="R04"; title="Classificação de Nuvens"; cat="rotinas"; obj="Identificar gêneros e cobertura de nuvens"; mat="Cartolina com gêneros"}
        @{id="R05"; title="Rosa dos Ventos Humana"; cat="rotinas"; obj="Identificar direção do vento"; mat="Bússola ou referências"}
        @{id="R06"; title="Amplitude Térmica Diária"; cat="rotinas"; obj="Medir diferença entre Tmáx e Tmín"; mat="Termômetro, formulário"}
        @{id="R07"; title="Previsão do Tempo do Aluno"; cat="rotinas"; obj="Elaborar previsão e confrontar depois"; mat="Formulário, observações"}
    )
    
    Write-Host "Criando $([array]$tutorials.Count) tutoriais..." -ForegroundColor Yellow
    
    foreach ($t in $tutorials) {
        $postId = & $wpCli post create `
            --post_type=remc_tutorial `
            --post_title="$($t.id) - $($t.title)" `
            --post_content="Guia para: $($t.title)" `
            --post_status=publish `
            --allow-root `
            --format=json
        
        & $wpCli term set $postId $($t.cat) remc_tutorial_cat --allow-root
        & $wpCli post meta set $postId _objective $t.obj --allow-root
        & $wpCli post meta set $postId _materials $t.mat --allow-root
        & $wpCli post meta set $postId _version "1.0" --allow-root
        
        Write-Host "Tutorial criado: $($t.id) - $($t.title)" -ForegroundColor Green
    }
}

# 9. Criar atividades
Write-Host "Criando atividades..." -ForegroundColor Yellow

$activities = @(
    @{title="Relatório - Nuvem na Garrafa"; author="aluno_joao"; type="experimento"}
    @{title="Previsão do Tempo - 24h"; author="aluno_maria"; type="previsao"}
)

foreach ($act in $activities) {
    $postId = & $wpCli post create `
        --post_type=remc_atividade `
        --post_title=$act.title `
        --post_author=$act.author `
        --post_status=publish `
        --allow-root `
        --format=json
    
    & $wpCli post meta set $postId _turma $turmas[0].title --allow-root
    & $wpCli post meta set $postId _tutorial_id "1" --allow-root
    & $wpCli post meta set $postId _hypothesis "Hipótese do aluno" --allow-root
    & $wpCli post meta set $postId _results "Resultados observados" --allow-root
    
    Write-Host "Atividade criada: $($act.title)" -ForegroundColor Green
}

Write-Host ""
Write-Host "=========================================="
Write-Host "Bootstrap concluído!" -ForegroundColor Green
Write-Host "=========================================="
Write-Host "Usuários criados:"
Write-Host "  - admin (administrador)"
Write-Host "  - professor_exemplo (professor)"
Write-Host "  - aluno_joao, aluno_maria, aluno_pedro (alunos)"
Write-Host "=========================================="
