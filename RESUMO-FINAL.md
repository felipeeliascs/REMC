# Resumo da Reconstrução REMC

**Data:** 2026-09-17  
**Versão:** 0.1.0 (MVP Inicial)  
**Corte tecnológico:** 31/01/2024  
**Ambiente:** Docker Compose + WordPress 6.4.3 + BuddyPress 12.2.0

## O que foi construído

### Plugin REMC Core (`wp-content/plugins/remc-core/`)
- 8 arquivos PHP em `/includes/`
- 5 CPTs: remc_escola, remc_local, remc_observacao, remc_tutorial, remc_atividade
- Sistema de permissões (4 papéis: administrador, professor, aluno, visitante)
- Validação de dados (temperatura, precipitação, RPM, barômetro, vento, nuvens)
- Workflow de observação (draft → pending → publish/devolvido)
- Relatórios e exportação CSV
- Gerenciamento de tutoriais
- Bootstrap via WP-CLI

### Tema REMC Educacional (`wp-content/themes/remc-educacional/`)
- 7 arquivos PHP (templates)
- 2 arquivos CSS
- 1 arquivo JS
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
docker-compose up -d
```

### Opção 2: PHP Built-in Server

```bash
cd wp-core
php -S 127.0.0.1:8000
```

### Opção 3: XAMPP/WAMP/Laragon

1. Copiar `wp-core` para `C:\xampp\htdocs\remc\`
2. Configurar banco de dados no phpMyAdmin
3. Acessar http://localhost/remc

## Arquivos principais

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
├── INICIAR-LOCAL.md
├── RESUMO-RECONSTRUCAO.md
├── wp-core/ (WordPress 6.4.3)
├── wp-content/
│   ├── plugins/remc-core/
│   └── themes/remc-educacional/
├── scripts/
└── docs/
```

## Próximos passos

- Testes automatizados (PHPUnit, Cypress)
- Gráficos interativos (Chart.js)
- Bootstrap idempotente completo

## Licença

Uso interno educacional.
