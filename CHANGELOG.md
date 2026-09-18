# CHANGELOG.md

## [0.5.0] — 2026-09-17 — Menu, Feed em carrossel, nova Home e Open-Meteo

### Adicionado
- **Serviço Open-Meteo** no plugin (`remc-core/includes/class-remc-weather.php`):
  geocoding de Cachoeira Paulista (cache 30 dias), previsão atual + diária
  (cache 15 min), mapeamento reutilizável de `weather_code` (OMM) para
  descrição + ícone, e rota REST `remc/v1/weather`. Nenhum outro ponto chama a
  API.
- **Nova Home** em partes (`template-parts/home/`): hero com CTAs reais, "Como
  funciona" (4 passos), "Tempo agora em Cachoeira Paulista" com previsão curta,
  "Cientista do dia" (pergunta trocável pelo filtro `remc_scientist_question`) e
  seção "Programa Educação" (`#programa-educacao`), preservando a lista de
  tutoriais.
- **Carrossel de observações** no Feed/Painel: uma observação por vez, com
  `← Anterior`, indicador `N de M`, `Próximo →`, navegação por teclado e limites
  respeitados. Estado explícito **Compartilhado no Feed** (com link para a
  publicação) ou **Compartilhar no Feed**; ícone + texto (não só cor).
- **Compartilhamento via AJAX** (`wp_ajax_remc_toggle_share`), reutilizando a
  mesma lógica do fluxo por formulário; os links `admin-post.php` continuam como
  fallback sem JavaScript.
- Menu responsivo com botão (`.menu-toggle`), submenu acessível e novo arquivo
  `assets/js/remc-ui.js`.

### Alterado
- **Menu principal**: "Timeline" → **Feed**; "Turmas" → **Turma**;
  "Minha timeline" → **Minha Timeline** (mesma rota `/activity/?scope=just-me`).
  Estrutura: `Início | Feed | Minha Timeline | Turma | Observações ▾` à esquerda
  e `Tutoriais | Programa Educação` à direita (classe `remc-menu-right`).
  Observações é dropdown com **Painel do Aluno** e **Área de Trabalho**.
  "Painel do Professor" permanece no grupo da direita, visível só para
  professor/administrador.
- O menu deixa de ser replicado no rodapé (atribuído apenas a `main`).
- `remc_share_panel()` passou a renderizar o carrossel (reutilizado pelo Painel
  do Aluno e pela página do Feed).

### Corrigido
- O seed do bootstrap sobrescrevia o estado de revisão de observações já
  existentes, o que podia deixar item pendente no Feed. Agora só define o estado
  ao criar.
- Rede de segurança `reconcile_on_save` garante que o Feed nunca mantenha
  observação não aprovada, mesmo quando o estado muda sem transição de
  `post_status`. Item pendente que estava no Feed foi removido.

### Verificado (HTTP real)
- Menu deslogado: Início, Feed, Turma, Tutoriais, Programa Educação (Minha
  Timeline oculta; Observações podado sem filhos visíveis). Logado como aluno:
  inclui Minha Timeline, Observações ▾ (Painel do Aluno, Área de Trabalho).
- Home: dados reais da Open-Meteo renderizados (ex.: 20,2 °C, "Chuviso leve"),
  previsão de 3 dias e REST `remc/v1/weather` respondendo 200.
- Falha simulada de rede retorna `WP_Error` e a Home mostra aviso, sem quebrar.
- Carrossel: 2 slides, prev/next, indicador, ambos os estados com ícone; AJAX
  compartilhar (idempotente, sem duplicar) e remover alteram o Feed.
- `/`, `/activity/`, `/painel-do-aluno/`, `/tutoriais/`, `/grupos/` em 200;
  `debug.log` limpo.

## [0.4.4] — 2026-09-17 — Estado "compartilhado" vira controle clicável

### Corrigido
- O estado compartilhado era exibido como **selo** (`<span class="badge">`)
  verde, "Compartilhado no feed", que parecia um botão mas **não era
  clicável** — a ação real ficava num botão separado abaixo. Agora há **um
  único controle clicável**: `✓ Compartilhado no feed — remover`, um link
  verde com `aria-label`
  "Remover esta observação do feed da comunidade". Quando não está
  compartilhado, permanece o botão "Compartilhar no feed".

### Verificado (HTTP real, login como aluno)
- Clique único: feed 1 → 0 (remover) e 0 → 1 (compartilhar).
- O selo não clicável (`badge-approved`) não é mais usado para o estado de
  compartilhamento; o controle tem classe `remc-shared` e cursor de link.
- `debug.log` limpo; `/`, `/activity/` e `/painel-do-aluno/` em 200.

## [0.4.3] — 2026-09-17 — Feedback do compartilhamento e correção do menu

### Corrigido
- O item de menu **"Minha timeline"** apontava para
  `/members/<login>/activity/`, que no BuddyPress 14 responde **301 para o
  perfil** — ou seja, levava a lugar nenhum útil. Passou a apontar para
  `/activity/?scope=just-me`, que funciona.
- O aviso de resultado do compartilhamento só aparecia nas páginas que
  renderizavam o painel. Agora é exibido no início do `<body>`
  (`wp_body_open`), então o retorno aparece em **qualquer** página de origem.
- `nocache_headers()` antes do redirecionamento, para o navegador não mostrar
  a página em cache (o que dava a impressão de que nada acontecia).

### Documentado
- `DESENVOLVIMENTO.md`: nota de que `/members/<login>/activity/` redireciona para o
  perfil no BP 14; a timeline do usuário é `/activity/?scope=just-me`.

### Verificado (HTTP real, login como aluno)
- Menu: "Minha timeline" -> `/activity/?scope=just-me` (200).
- Ação a partir de `/activity/` e de `/members/aluno_joao/` altera o feed
  (1 -> 0 ao remover, 0 -> 1 ao compartilhar) e o aviso aparece na página de
  retorno.
- `debug.log` sem erros de front-end.

## [0.4.2] — 2026-09-17 — Compartilhar direto na página do feed

### Corrigido
- A página `/activity/` não oferecia nenhum caminho de compartilhamento (o
  formulário de texto livre foi removido de propósito), o que dava a impressão
  de que "não dá para compartilhar nada". Agora o tema exibe ali o bloco
  **"Compartilhar dados meteorológicos"** para usuários logados, com a mesma
  prévia e os botões de compartilhar/remover.

### Alterado
- O bloco de compartilhamento virou a função reutilizável `remc_share_panel()`
  (tema), usada no Painel do Aluno e na página de atividades — evita duplicação.
- O override `buddypress/activity/post-form.php` deixou de ser vazio: ele chama
  `remc_activity_share_panel()`, cobrindo o diretório de atividades, a atividade
  do próprio perfil e a atividade de grupo.

### Verificado (HTTP real)
- Deslogado: `/activity/` sem o bloco de compartilhamento.
- Logado como aluno: `/activity/` mostra o bloco, a prévia e o botão; o feed vai
  de 1 → 0 (remover) e 0 → 1 (compartilhar). Sem formulário de texto livre.
- `debug.log` limpo; `/`, `/activity/` e `/painel-do-aluno/` em 200.

## [0.4.1] — 2026-09-17 — Formulário das variáveis e compartilhamento no Painel

### Corrigido
- **O aluno não conseguia compartilhar.** A ação existia e era autorizada, mas o
  botão ficava na tela de edição — inacessível para observações aprovadas (o
  autor não tem `edit_post` nelas, por regra). O compartilhamento passou para o
  **Painel do Aluno**, com prévia pública e retorno visual
  (`?remc_feed=shared|unshared|error`).
- **Aluno podia se auto-aprovar.** `managed_turmas()` considerava
  `_linked_turmas` de qualquer usuário, então um aluno era tratado como
  responsável pela turma. Agora exige papel `professor` ou `administrator`.
- **Aprovação docente falhava** porque a checagem "pelo menos uma variável"
  olhava apenas o POST. Agora considera o que já está salvo
  (`observation_has_variable`).
- **BOM** em `class-remc-post-types.php`, `compose.yaml` e `.env.example`
  causava "Cannot modify header information ... output started at ...:1",
  quebrando login e REST. Removido.
- Acentuação corrompida em `class-remc-post-types.php` (dupla codificação).
- Registro sem variáveis reconhecidas não aparece mais com botão de
  compartilhamento; mostra o motivo e, se possível, o link de edição.

### Adicionado
- **Formulário das variáveis da observação** (o CPT antes só tinha data, turma,
  local e situação, o que levava a campos personalizados arbitrários):
  temperatura do ar, Tmín/Tmáx, precipitação com início/fim, anemômetro
  (voltas/segundos e RPM calculada), barômetro (referência, deslocamento e
  orientação), direção e intensidade do vento, condição do céu, cobertura (0–8
  + especiais), gêneros de nuvens (múltipla escolha), método, instrumento,
  versão do protocolo e notas.
- Validação no salvamento por `Remc_Validation` (vírgula decimal, faixas,
  duração > 0, RPM, períodos) com aviso em tela e exigência de ao menos uma
  variável.
- Situação de revisão restrita: aluno só envia para revisão; aprovar/devolver é
  exclusivo de professor responsável ou administrador.

### Alterado
- `custom-fields` removido de `remc_observacao` e `remc_atividade` (evita
  chaves arbitrárias como a que gerou o registro sem dados).

### Verificado (HTTP real, login como aluno)
- Painel lista a observação aprovada com prévia; "Compartilhar no feed" leva o
  feed de 0 → 1 e "Remover do feed" de 1 → 0.
- Registro sem variáveis não exibe botão de compartilhamento.
- Fluxo: aluno não auto-aprova; envia para revisão; professor aprova;
  compartilhamento funciona; duplicar não duplica; apagar remove do feed.
- `debug.log` limpo.

## [0.4.0] — 2026-09-17 — Menu principal e correção do modelo de permissões

### Corrigido (falhas graves e pré-existentes)
- **Papéis do projeto nunca eram criados.** `Remc_Roles_Capabilities` só chamava
  `get_role()`, então `professor`, `aluno` e `visitante` não existiam: os
  usuários ficavam sem papel e as capabilities eram decorativas. Agora os papéis
  são criados com `add_role()` e recebem as capabilities corretas.
- **Autorização por objeto não funcionava.** Havia vazamento (aluno de outra
  turma lia observações) e bloqueio indevido (professor responsável não podia
  revisar). A verificação migrou para o filtro `map_meta_cap`, devolvendo
  `do_not_allow` quando o vínculo com escola/turma não confere.
- **Capabilities dos CPTs geravam nomes errados** (`edit_remc_observacaos`).
  Agora há mapa explícito de capabilities por CPT (`edit_observacao`,
  `edit_published_observacoes`, etc.).
- **`edit_published_posts` caía numa capability genérica**, contornando os
  controles por tipo de conteúdo. Resolvido pelo mapa explícito.
- **Meta boxes listavam `post_type=group`**, que não existe no BuddyPress (a
  lista de turmas vinha vazia). Agora usam `groups_get_groups()` filtrado pelas
  turmas do usuário.
- Vínculo de turma validado no salvamento: não é possível registrar dados em
  turma da qual não se participa (`validate_scope`).

### Adicionado
- Menu principal (localização `main`) criado/reconciliado pelo bootstrap, com
  itens dinâmicos por perfil resolvidos no tema
  (`remc_filter_nav_menu_objects`): Minha timeline, Meu perfil, Painel do Aluno,
  Painel do Professor e Área de trabalho aparecem só para quem se aplica.
- Páginas "Painel do Aluno" e "Painel do Professor" (templates do tema) criadas
  pelo bootstrap.
- Arquivo público de tutoriais (`has_archive`), servido em `/tutoriais/`.
- Permalinks amigáveis (`/%postname%/`) na instalação e na modernização.

### Verificado
- Autorização: admin `edit/read/delete=true`; professor A `edit/read=true/delete=false`;
  professor B e aluno de outra turma `false`; autor não edita observação
  aprovada (só após reabertura); colega de turma lê somente aprovadas.
- `managed_turmas`: professor A → `[1]`, professor B → `[2]`.
- Menu: visitante vê Início/Timeline/Tutoriais/Turmas; aluno e professor veem
  também seus painéis; URLs residem em `/activity/`, `/grupos/`, `/tutoriais/`.
- HTTP 200 em `/`, `/activity/`, `/tutoriais/`, `/grupos/`, `/painel-do-aluno/`;
  `debug.log` limpo; feed REST com 1 item.

## [0.3.0] — 2026-09-17 — Rede social de dados meteorológicos

### Adicionado
- `remc-core/includes/class-remc-activity.php`: feed social no componente
  `activity` do BuddyPress, com ação própria `remc_shared_observation`.
- Compartilhamento **opt-in manual** pelo aluno, somente de observação aprovada,
  com prévia exata do texto que ficará público.
- Interações (comentar/curtir) restritas a membros da turma; visitante apenas lê.
- Visibilidade pública limitada aos itens do componente `remc`.
- Demonstração no bootstrap: compartilha a observação de chuva (idempotente).

### Corrigido
- Vocabulário de revisão inconsistente: o bootstrap e o meta box gravavam
  `_status` como `publish/pending` em vez de `aprovado/pendente`. Corrigido e
  dados existentes migrados pela reexecução do bootstrap.
- Filtro de diretório público montava SQL inválido
  (`Unknown column 'Array'`); agora usa uma condição SQL válida.
- Cache de autorização por item ignorava o usuário; passou a incluir o
  `user_id` na chave.
- `bp_get_activity_id()` usado fora do loop causava warnings; trocado por acesso
  seguro ao template global.
- Removido código morto `class-remc-bootstrap.php` (não era carregado).

### Alterado
- Componente `activity` passa a ficar **ativo** (feed social); `notifications`,
  `friends`, `messages` e `blogs` permanecem desativados.
- Override de tema `buddypress/activity/post-form.php` remove o formulário de
  texto livre do feed.

### Verificado
- REST público `/buddypress/v1/activity` retorna o item compartilhado e não
  expõe pendentes.
- Devolver a observação remove o item do feed; reaprovar não republica sozinho;
  recompartilhar não duplica.
- `deslogado=false`, `turma B=false`, `turma A=true` para interação.

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
  `a52778c` da reconstrução.
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
