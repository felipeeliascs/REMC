# Documentação - Arquitetura REMC

## Visão Geral

A REMC é construída sobre WordPress 6.4.3 com BuddyPress 12.2.0, usando uma arquitetura modular com:
- **Plugin principal** (`remc-core`): lógica de negócios, CPTs, permissões
- **Tema leve** (`remc-educacional`): interface visual, templates
- **Grupos BuddyPress**: turmas como grupos ocultos (`hidden`)

## Estrutura do Código

```
remc/
├── wp-content/
│   ├── plugins/
│   │   └── remc-core/
│   │       ├── remc-core.php (bootstrap)
│   │       └── includes/
│   │           ├── class-remc-post-types.php
│   │           ├── class-remc-roles-capabilities.php
│   │           ├── class-remc-validation.php
│   │           ├── class-remc-notifications.php
│   │           ├── class-remc-reports.php
│   │           └── class-remc-tutorials.php
│   └── themes/
│       └── remc-educacional/
│           ├── functions.php
│           ├── style.css
│           ├── index.php
│           ├── front-page.php
│           ├── template-student-dashboard.php
│           ├── template-teacher-dashboard.php
│           ├── template-tutorial.php
│           └── assets/
│               ├── css/
│               │   ├── charts.css
│               │   └── forms.css
│               └── js/
│                   └── scripts.js
├── scripts/
│   ├── setup.sh
│   └── bootstrap.ps1
└── docs/
    ├── requisitos.md
    ├── arquitetura.md
    ├── dicionario-de-dados.md
    ├── catalogo-de-atividades.md
    ├── permissoes.md
    ├── versoes.md
    ├── testes.md
    └── cronologia.csv
```

## CPTs (Custom Post Types)

| CPT | Finalidade | Visibilidade | Permissões |
|-----|------------|--------------|------------|
| `remc_escola` | Escolas | Admin | administrator, professor (vinculado) |
| `remc_local` | Pontos de observação | Admin | administrator, professor (vinculado) |
| `remc_observacao` | Registros meteorológicos | Admin | aluno (criação), professor (revisão) |
| `remc_tutorial` | Guias didáticos | Público (publish) | professor, administrator |
| `remc_atividade` | Relatórios didáticos | Admin | aluno (criação), professor (revisão) |

## Taxonomias

| Taxonomia | CPTs | Finalidade |
|-----------|------|------------|
| `remc_tutorial_cat` | `remc_tutorial` | Categoria (instrumentos, experimentos, rotinas) |
| `remc_metodo` | `remc_observacao` | Método de coleta (instrumento, artesanal, visual) |

## Permissões

### Capabilities Personalizadas

```php
$caps = array(
    // Escolas
    'read_escola', 'edit_escola', 'delete_escola', 'create_escola',
    // Locais
    'read_local', 'edit_local', 'delete_local', 'create_local',
    // Observações
    'read_observacao', 'edit_observacao', 'delete_observacao', 'create_observacao',
    'publish_observacao', 'approve_observacao',
    // Tutoriais
    'read_tutorial', 'edit_tutorial', 'delete_tutorial', 'create_tutorial',
    'publish_tutorial', 'edit_others_tutorials', 'delete_others_tutorials',
    // Atividades
    'read_atividade', 'edit_atividade', 'delete_atividade', 'create_atividade',
    // Admin
    'export_data', 'manage_escolas', 'manage_turmas', 'manage_alunos',
    'reset_student_password',
);
```

### Mapping de Permissões

As permissões são validadas em `Remc_Roles_Capabilities::filter_user_caps()` com regras por objeto:

- **Escola**: vinculada a professores específicos
- **Local**: vinculado a turma e escola
- **Observação**: verificação de turma do aluno e professor
- **Atividade**: verificação de turma do aluno

## Fluxo de Observação

```
Aluno cria rascunho
    ↓
Aluno envia (status: pending)
    ↓
Professor recebe na fila de revisão
    ↓
Professor aprova (status: publish) ou devolve (status: devolvido)
    ↓
Aluno recebe notificação e corrige (se devolvido)
    ↓
Aluno reenvia
    ↓
Professor aprova
    ↓
Dados entram na série histórica da turma
```

## Grupos BuddyPress (Turmas)

- Tipo: `hidden` (não aparecem em diretórios)
- Acesso: apenas por convite
- Meta: `professor_responsavel` (ID do professor)
- Não permitem inscrição pública, amizades ou mensagens privadas

## Validations

`Remc_Validation` fornece métodos para:
- `validate_temperature()`: -90°C a +60°C, aceita vírgula decimal
- `validate_precipitation()`: não negativo
- `validate_anemometer()`: calcula RPM = 60 × voltas / segundos
- `validate_barometer()`: deslocamento em mm (pode ser negativo)
- `validate_wind_direction()`: 8 direções + calmaria/variável/indeterminada
- `validate_cloud_cover()`: 0-8 oitavos + valores especiais
- `validate_cloud_genera()`: 10 gêneros OMM + não identificado
- `check_duplicate_observation()`: evita duplicatas por local/horário/variável

## Relatórios e Gráficos

`Remc_Reports` fornece:
- `get_chart_data()`: consulta com filtros (turma, local, variável, período)
- `calculate_aggregates()`: média, mínimo, máximo, contagem
- `export_csv()`: UTF-8, neutralização de fórmulas
- AJAX endpoints: `remc_get_chart_data`, `remc_export_csv`

## Tutoriais

13 guias distribuídos em 3 categorias:
- **Instrumentos**: I01 (Pluviômetro PET), I02 (Anemômetro), I03 (Barômetro)
- **Experimentos**: E01 (Nuvem na Garrafa), E02 (Ciclo da Água), E03 (Duas Vasilhas)
- **Rotinas**: R01-R07 (Pluviométrico, Pressão, RPM, Nuvens, Rosa dos Ventos, Amplitude, Previsão)

## Notificações

`Remc_Notifications`:
- Notifica aluno quando observação é devolvida
- Notifica aluno quando observação é aprovada
- Notifica professor quando nova observação aguarda revisão

## Integração BuddyPress

- Grupos usados como turmas (não modifica BP core)
- Profiles para dados de usuário (não expõe dados sensíveis)
- Sem atividade social (desativado no MVP)

## Segurança

- Nonce em todos os formulários
- Capabilities por objeto (não apenas por papel)
- Validação no servidor para todos os dados
- Sanitização de entrada
- Prepared statements no banco
- Permissões em todas as páginas, feeds, AJAX

## Limitações Conhecidas

- Sem Multisite
- Apenas localhost (127.0.0.1)
- Sem atualizações automáticas no ambiente local
- Sem PDF (apenas CSV e visualização no navegador)
- Sem gráficos interativos no navegador (placeholder para Chart.js)
- Sem geolocalização ou mapas

## Versionamento

- Versão fixada: WordPress 6.4.3, BuddyPress 12.2.0
- PHP 8.1.x, MariaDB 10.6.x
- Data de corte: 31/01/2024
- Versão do plugin: 0.1.0 (MVP inicial)
