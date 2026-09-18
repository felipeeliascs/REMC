# DESENVOLVIMENTO.md — REMC

## Contexto

REMC — Rede Educacional de Monitoramento Climático
Reconstrução de MVP WordPress + BuddyPress (início de 2024)

## Versões tecnológicas

### Ambiente atual (modernizado em 17/09/2026)

- WordPress: 7.1
- BuddyPress: 14.5.2
- PHP: 8.3.x (imagem `wordpress:7.1-php8.3-apache`)
- Banco: MariaDB 10.6.16

### Reconstrução histórica (corte 31/01/2024, preservada no commit `c72b5da`)

- WordPress: 6.4.3 (30/01/2024)
- BuddyPress: 12.2.0 (23/01/2024)
- PHP: 8.1.x
- Banco: MariaDB 10.6.x
- Data de corte: 31/01/2024

Detalhes em `docs/versoes.md`.

## Comandos principais

### Iniciar ambiente completo (recomendado)

```powershell
# Requer Docker Desktop em execucao (Engine running)
.\scripts\remc-init.ps1
```

O script: sobe os containers, instala o WordPress 6.4.3, instala e ativa o
BuddyPress 12.2.0, ativa os componentes necessarios, ativa o plugin/tema,
aplica pt_BR + America/Sao_Paulo e roda o bootstrap de dados ficticios.

### Modernizar a stack (WordPress 7.1 + BuddyPress 14.5.2 + PHP 8.3)

```powershell
.\scripts\remc-modernize.ps1
```

Executa pull das imagens modernas, recria o container, atualiza o BuddyPress,
reativa componentes/plugin/tema e mostra as versoes. O estado historico
permanece no commit `c72b5da`.

### Iniciar/parar containers

```powershell
docker compose up -d mariadb wp
docker compose down
```

### Executar comandos WP-CLI

O contêiner `wpcli` é efêmero; use `docker compose run`:

```powershell
docker compose run --rm -T wpcli <comando wp>
```

### Instalar WordPress (equivalente manual)

```bash
wp core install \
  --url=http://127.0.0.1 \
  --title="REMC" \
  --admin_user=admin_remc \
  --admin_password=$(WP_ADMIN_PASSWORD) \
  --admin_email=admin@remc.local \
  --allow-root
```

### Ativar plugins e tema

```bash
wp plugin activate buddypress remc-core --allow-root
wp theme activate remc-educacional --allow-root
wp bp component activate xprofile settings groups --allow-root
```

### Executar bootstrap de dados fictícios (idempotente)

```bash
wp remc bootstrap --allow-root
wp remc bootstrap --force --allow-root   # remove e recria os dados de demonstração
```

### Comandos úteis

```bash
# Verificar versão do WordPress
wp core version --allow-root

# Verificar instalação
wp core is-installed --allow-root

# Ver usuários
wp user list --allow-root

# Ver grupos (turmas ocultas)
wp bp group list --fields=id,name,status --allow-root

# Ver CPTs
wp post type list --allow-root
```

### Acessos locais (dados fictícios)

| Perfil | Login | Senha |
|--------|-------|-------|
| Administrador | `admin_remc` | `admin_password_123` |
| Professor A (Turma A) | `professor_a` | `professor_password_123` |
| Professor B (Turma B) | `professor_b` | `professor_password_123` |
| Aluno (Turma A) | `aluno_joao` / `aluno_maria` | `aluno_password_123` |
| Aluno (Turma B) | `aluno_pedro` | `aluno_password_123` |

### Timeline / feed social (URLs)

Com permalinks amigáveis habilitados (`/%postname%/`):

| Tela | URL |
|------|-----|
| Feed da comunidade (Activity) | `http://127.0.0.1/activity/` |
| Minha Timeline (escopo do usuário logado) | `http://127.0.0.1/activity/?scope=just-me` |
| Perfil de um aluno | `http://127.0.0.1/members/aluno_joao/` |
| Diretório de turmas | `http://127.0.0.1/grupos/` |
| Biblioteca de tutoriais | `http://127.0.0.1/tutoriais/` |
| Painel do Aluno | `http://127.0.0.1/painel-do-aluno/` |
| Painel do Professor | `http://127.0.0.1/painel-do-professor/` |
| Programa Educação (âncora na Home) | `http://127.0.0.1/#programa-educacao` |
| REST do feed | `http://127.0.0.1/wp-json/buddypress/v1/activity` |
| REST do clima (Open-Meteo) | `http://127.0.0.1/wp-json/remc/v1/weather` |

> **Nota (BuddyPress 14):** `http://127.0.0.1/members/<login>/activity/` **não**
> é uma timeline utilizável — o BuddyPress redireciona (301) para o perfil.
> Use o diretório com escopo `?scope=just-me` para a atividade do usuário
> logado. Por isso o item de menu "Minha Timeline" aponta para essa URL.

O **menu principal** (localização `main`, criado/reconciliado pelo bootstrap):

```
[ Início | Feed | Minha Timeline | Turma | Observações ▾ ]   [ Tutoriais | Programa Educação ]
                                        └── Painel do Aluno
                                        └── Área de Trabalho
```

- Itens com URL `#remc-*` são resolvidos/ocultados pelo tema conforme o perfil
  (`remc_filter_nav_menu_objects` em `functions.php`); pais de submenu sem filhos
  visíveis são podados.
- "Painel do Professor" fica no grupo da direita e só aparece para
  professor/administrador.
- O grupo da direita usa a classe `remc-menu-right` (Flexbox).
- Abaixo de 782px o menu vira botão (`.menu-toggle`) com submenu em acordeão
  (`assets/js/remc-ui.js`).

**Feed (Activity):** a página `/activity/` mostra o carrossel de observações
aprovadas (uma por vez, com `← Anterior`, `N de M`, `Próximo →`), o estado
"Compartilhado no Feed" (com link para a publicação) ou "Compartilhar no Feed",
e a lista do feed do BuddyPress. O compartilhamento usa `admin-post.php`
(fallback sem JS) e `wp_ajax_remc_toggle_share` (AJAX).

**Clima:** toda a integração com a Open-Meteo fica em
`remc-core/includes/class-remc-weather.php` (geocoding + previsão + cache +
mapeamento de `weather_code`). A Home apenas consome `Remc_Weather::get_weather()`
(renderizada no servidor; se a API falhar, mostra aviso e o resto da página
funciona).

Sem permalinks amigáveis (fallback por query var):
`http://127.0.0.1/?bp_activities=1` e `http://127.0.0.1/?bp_members=1&bp_member=aluno_joao`

O aluno compartilha em **wp-admin → Observações → abrir a observação aprovada**
(meta box "Feed REMC" → "Compartilhar no feed").

## Estrutura

```
remc/
  DESENVOLVIMENTO.md
  README.md
  CHANGELOG.md
  RECONSTRUCTION.md
  .gitignore
  .env.example
  compose.yaml
  wp-content/
    plugins/remc-core/
    themes/remc-educacional/
  scripts/
  tests/
  docs/
```

## Arquitetura

- **Plugin remc-core**: regras de negócio, CPTs, permissões, API
- **Tema remc-educacional**: visual, templates, assets
- **CPTs**:
  - `remc_escola`: escolas
  - `remc_local`: pontos de observação
  - `remc_observacao`: registros meteorológicos
  - `remc_tutorial`: guias didáticos
  - `remc_atividade`: relatórios didáticos

## Permissões

- **Administrador**: configuração completa
- **Professor**: gestão de turmas (BuddyPress groups), escolas, revisão
- **Aluno**: observações e atividades próprias
- **Visitante**: acesso público limitado a tutoriais

## Testes

- Unitários: PHP ( PHPUnit )
- Integração: Cypress / testes manuais no navegador
- Verificar autorização por objeto, validação, cálculos

## Limites conhecidos

- Sem Multisite
- Apenas localhost (127.0.0.1)
- Sem atualizações automáticas no ambiente local
- Exportação CSV, mas não PDF no MVP
- Gráficos no navegador (placeholder para Chart.js)
- Sem geolocalização ou mapas

## Origens

- Documento funcional: julho de 2025
- Recuperado de: documentação posterior, não evidência histórica
- Data histórica: 31/01/2024 (suposição executável)
