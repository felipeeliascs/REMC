# CHANGELOG.md

## [0.1.0] — 2026-09-17 — Reconstrução MVP REMC

### Adicionado
- Estrutura de diretórios: `wp-content/plugins/remc-core/`, `wp-content/themes/remc-educacional/`, `scripts/`, `tests/`, `docs/`
- Arquivos de configuração: `.env.example`, `.env`, `compose.yaml`, `.gitignore`
- Plugin `remc-core` com:
  - 5 CPTs: remc_escola, remc_local, remc_observacao, remc_tutorial, remc_atividade
  - Sistema de permissões com 4 papéis
  - Validação de dados (temperatura, precipitação, RPM, barômetro, vento, nuvens)
  - Workflow de observação (draft → pending → publish/devolvido)
  - Relatórios e exportação CSV
  - Gerenciamento de tutoriais
- Tema `remc-educacional` com templates para painel aluno/professor e tutoriais
- 13 tutoriais (3 instrumentos, 3 experimentos, 7 rotinas)
- Bootstrap com dados fictícios via WP-CLI
- Documentação completa em `docs/`
- Scripts PowerShell (bootstrap.ps1) e shell (setup.sh)

### Notas
- Versão baseada em WordPress 6.4.3 (30/01/2024) e BuddyPress 12.2.0 (23/01/2024)
- Data de corte tecnológica: 31/01/2024
- Ambiente local reproduzível via Docker Compose
- Dados fictícios claramente identificados
