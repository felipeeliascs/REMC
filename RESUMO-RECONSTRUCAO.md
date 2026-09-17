# Resumo da Reconstrução REMC

**Data:** 2026-09-17  
**Versão:** 0.1.0 (MVP Inicial)  
**Corte tecnológico:** 31/01/2024  
**Ambiente:** Docker Compose + WordPress 6.4.3 + BuddyPress 12.2.0

## O que foi construído

### Plugin REMC Core (`wp-content/plugins/remc-core/`)
- 5 CPTs: remc_escola, remc_local, remc_observacao, remc_tutorial, remc_atividade
- Sistema de permissões (administrador, professor, aluno, visitante)
- Validação de dados (temperatura, precipitação, RPM, barômetro, vento, nuvens)
- Workflow de observação (draft → pending → publish/devolvido)
- Relatórios e exportação CSV
- Gerenciamento de tutoriais
- Bootstrap via WP-CLI

### Tema REMC Educacional (`wp-content/themes/remc-educacional/`)
- Templates: página inicial, painel aluno, painel professor, tutoriais
- CSS responsivo e acessível
- Scripts JavaScript para interações

### 13 Tutoriais
- 3 instrumentos (pluviômetro, anemômetro, barômetro)
- 3 experimentos (nuvem, ciclo da água, vasilhas)
- 7 rotinas (pluviométrico, pressão, RPM, nuvens, rosa dos ventos, amplitude, previsão)

### Dados Fictícios
- 1 escola, 1 professor, 3 alunos, 2 turmas
- 4 pontos de observação, 4 observações de exemplo
- 2 atividades

## Como rodar localmente

### Opção 1: Docker Compose (recomendado)

```bash
# Iniciar ambiente
docker-compose up -d

# Acessar WordPress
http://127.0.0.1
```

### Opção 2: Servidor PHP local (CLI)

```bash
# Instalar WordPress
cd wp-content
wp core download
cp ../.env.example .env
# Editar .env com suas configurações

# Configurar banco de dados localmente
# E executar WordPress com PHP built-in
php -S 127.0.0.1:8000 -t .
```

### Opção 3: XAMPP/WAMP/Laragon

1. Copiar `wp-content` para o diretório do servidor
2. Importar banco (opcional, usar dados fictícios)
3. Configurar `.env`
4. Acessar http://localhost

## Comandos WP-CLI

```bash
# Instalar WordPress
wp core install --url=http://127.0.0.1 --title=REMC --admin_user=admin --admin_password=senha123

# Ativar plugin
wp plugin activate remc-core

# Ativar tema
wp theme activate remc-educacional

# Executar bootstrap
wp remc bootstrap

# Criar novos dados
wp remc bootstrap --force
```

## Arquivos criados

```
remc/
├── DESENVOLVIMENTO.md
├── README.md
├── CHANGELOG.md
├── RECONSTRUCTION.md
├── .gitignore
├── .env.example
├── .env
├── compose.yaml
├── wp-content/
│   ├── plugins/remc-core/
│   │   ├── remc-core.php
│   │   └── includes/*.php (8 arquivos)
│   └── themes/remc-educacional/
│       ├── functions.php
│       ├── style.css
│       ├── index.php
│       ├── front-page.php
│       ├── template-*.php (4 arquivos)
│       └── assets/
└── scripts/
    ├── setup.sh
    └── bootstrap.ps1
└── docs/
    └── *.md (9 arquivos)
```

## Próximos passos

- Testes automatizados (PHPUnit, Cypress)
- Gráficos interativos (Chart.js)
- Bootstrap idempotente completo
- Documentação de testes

## Licença

Uso interno educacional.
