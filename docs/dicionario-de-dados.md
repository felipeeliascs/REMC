# Documentação - Dicionário de Dados REMC

## Entidades Principais

### Escola (`remc_escola`)
Representa instituições educacionais participantes.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `post_title` | string | Sim | Nome da escola |
| `post_status` | enum | Sim | publish, draft |
| `meta: _linked_professors` | array | Não | IDs dos professores vinculados |

### Local de Observação (`remc_local`)
Ponto físico onde observações são realizadas (escola ou residência).

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `post_title` | string | Sim | Nome do ponto (ex: "Ponto Escola A") |
| `post_status` | enum | Sim | publish, draft |
| `meta: _turma` | int | Sim | ID do grupo BuddyPress (turma) |
| `meta: _tipo` | enum | Sim | escola, casa |
| `meta: _pluviometer` | string | Sim | Código do instrumento |
| `meta: _pluviometer_version` | string | Sim | Versão do protocolo |

### Observação (`remc_observacao`)
Registro estruturado de medição meteorológica.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `post_title` | string | Sim | Título da observação |
| `post_author` | int | Sim | ID do aluno autor |
| `post_status` | enum | Sim | draft, pending, publish, devolvido |
| `meta: _turma` | int | Sim | ID da turma |
| `meta: _local_id` | int | Sim | ID do local |
| `meta: _observation_date` | datetime | Sim | Data/hora da observação (UTC) |
| `meta: _temperature_air` | float | Não | Temperatura do ar (°C) |
| `meta: _precipitation` | float | Não | Precipitação (mm) |
| `meta: _precipitation_start` | datetime | Não | Início da acumulação |
| `meta: _precipitation_end` | datetime | Não | Fim da acumulação |
| `meta: _instrument` | string | Sim | Código do instrumento |
| `meta: _instrument_version` | string | Sim | Versão do protocolo |
| `meta: _wind_direction` | enum | Não | N, NE, L, SE, S, SO, O, NO, calmaria, variavel, naoobservado |
| `meta: _wind_intensity` | string | Não | Categoria qualitativa |
| `meta: _anemometer_rotations` | int | Não | Contagem de voltas |
| `meta: _anemometer_seconds` | int | Não | Duração da contagem |
| `meta: _anemometer_rpm` | float | Não | RPM calculado (60 × voltas / segundos) |
| `meta: _barometer_reference` | string | Não | Linha de referência |
| `meta: _barometer_displacement` | float | Não | Deslocamento do ponteiro (mm) |
| `meta: _sky_condition` | enum | Não | Limpo, parcialmente nublado, nublado, etc. |
| `meta: _cloud_genera` | array | Não | Gêneros de nuvens (10 OMM) |
| `meta: _cloud_cover` | enum | Não | 0-8, naoobservado, obscurecido, indeterminavel |
| `meta: _temperature_min` | float | Não | Tmín (°C) |
| `meta: _temperature_max` | float | Não | Tmáx (°C) |
| `meta: _method` | string | Sim | Instrumento calibrado, artesanal, estimativa, visual |
| `meta: _notes` | text | Não | Observações adicionais |
| `meta: _status` | enum | Sim | rascunho, pendente, aprovado, devolvido |
| `meta: _reviewer_id` | int | Não | ID do professor |
| `meta: _review_comment` | text | Não | Comentário da revisão |
| `meta: _reopened_at` | datetime | Não | Data de reabertura |

### Tutorial (`remc_tutorial`)
Guia didático publicado para oficinas.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `post_title` | string | Sim | Nome da atividade |
| `post_status` | enum | Sim | draft, publish |
| `tax: remc_tutorial_cat` | term | Sim | instrumentos, experimentos, rotinas |
| `meta: _objective` | text | Sim | Objetivo pedagógico |
| `meta: _materials` | text | Sim | Lista de materiais |
| `meta: _steps` | text | Sim | Etapas do procedimento |
| `meta: _reading_mode` | string | Sim | Forma de leitura |
| `meta: _unit` | string | Sim | Unidade de medida |
| `meta: _limitations` | text | Sim | Limitações do procedimento |
| `meta: _precautions` | text | Sim | Cuidados necessários |
| `meta: _collection_fields` | text | Sim | Campos a serem preenchidos |
| `meta: _version` | string | Sim | Versão do protocolo |

### Atividade (`remc_atividade`)
Relatório didático privado do aluno (experimento, previsão).

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `post_title` | string | Sim | Título da atividade |
| `post_author` | int | Sim | ID do aluno |
| `post_status` | enum | Sim | draft, pending, publish |
| `meta: _turma` | int | Sim | ID da turma |
| `meta: _tutorial_id` | int | Não | ID do tutorial relacionado |
| `meta: _hypothesis` | text | Sim | Hipótese (previsão) |
| `meta: _procedure` | text | Sim | Procedimento realizado |
| `meta: _results` | text | Sim | Resultados observados |
| `meta: _reflection` | text | Sim | Reflexão do aluno |
| `meta: _teacher_comment` | text | Não | Comentário do professor |
| `meta: _teacher_rating` | int | Não | Nota/avaliação |

## Estados e Transições

### Observação
- **draft** → **pending**: aluno envia para revisão
- **pending** → **publish** (aprovado): professor aprova
- **pending** → **devolvido** → **pending**: professor devolve com comentário
- **publish** → **devolvido** (reabertura): professor devolve após aprovação

### Tutorial
- **draft** → **publish**: administrador publica

### Atividade
- **draft** → **pending**: aluno envia para revisão
- **pending** → **publish** (aprovado): professor aprova
- **pending** → **devolvido** → **pending**: professor devolve

## Permissões por Papel

### Administrador
- Gerenciar escolas, professores e turmas
- Publicar tutoriais
- Aprovar/reviver observações de qualquer turma
- Exportar dados de todas as turmas

### Professor
- Criar e gerenciar turmas apenas em suas escolas
- Aprovar/devolver observações de sua turma
- Ver e exportar dados de sua turma
- Editar tutoriais próprios

### Aluno
- Criar observações em locais autorizados
- Criar atividades
- Ver dados de sua turma (aprovados)
- Editar apenas seus próprios registros

### Visitante
- Apenas acesso público a tutoriais publicados
