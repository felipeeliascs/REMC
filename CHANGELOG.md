# CHANGELOG.md

## [0.2.0] — 2026-09-17 — Modernização da stack (WordPress 7.1 + BuddyPress 14.5.2)

### Alterado
- `compose.yaml`: imagem do WordPress para `wordpress:7.1-php8.3-apache` e
  `wordpress:cli-php8.3` (PHP 8.1.34 → 8.3.33).
- BuddyPress atualizado de 12.2.0 para **14.5.2**.
- WordPress atualizado de 6.4.3 para **7.1** (core no volume + imagem fixada).
- `scripts/remc-modernize.ps1`: script de modernização reproduzível
  (pull, recriação do container, atualização do BP, componentes e verificação).
- `docs/versoes.md`: seção "Modernização da stack", preservando as versões
  históricas.
- `RECONSTRUCTION.md`: ordem dos commits e distinção entre reconstrução e
  modernização.

### Corrigido
- Aviso do BuddyPress 12.2.0 em WordPress ≥ 6.7
  (`_load_textdomain_just_in_time`), resolvido pela atualização do BuddyPress.
- `activity` e `notifications` desativados, pois o BP 14.x os ativa por padrão
  e o MVP exige o feed de atividades desativado.

### Preservado
- O estado histórico (WordPress 6.4.3 + BuddyPress 12.2.0) permanece no commit
  `e0019b5` da reconstrução.
- MariaDB mantido em 10.6.16 (compatível com o BP 14.x, sem migração de dados).

### Verificado
- HTTP 200, sem avisos no `debug.log`.
- Bootstrap idempotente sem duplicação.
- Validações: 15 voltas/30 s = 30 RPM; duração 0 recusada; `0 mm` = 0;
  `23,5` → 23.5; direção `NO` preservada.
- Grupos Turma A e Turma B permanecem `hidden`.

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
