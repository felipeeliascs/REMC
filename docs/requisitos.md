# Documentação - Requisitos REMC

## Visão Geral

REMC (Rede Educacional de Monitoramento Climático) é uma plataforma educacional para ciência cidadã, permitindo que alunos observem e registrem condições meteorológicas em casa e na escola, com fluxo de revisão docente e biblioteca de oficinas práticas.

## Requisitos Funcionais

### RF01 - Autenticação e Permissões
- Sistema de usuários WordPress com perfis: administrador, professor, aluno, visitante
- Grupos BuddyPress como turmas (ocultos)
- Autorização por objeto (não apenas por papel)
- Controle de senhas: professor só redefine senha de alunos vinculados exclusivamente a ele

### RF02 - Gestão de Escolas
- Criar, editar, visualizar escolas
- Vincular professores a escolas
- Não armazenar dados pessoais de escolas

### RF03 - Gestão de Turmas
- Criar turmas como grupos BuddyPress (ocultos, hidden)
- Vincular professores como responsáveis
- Inscrever alunos em turmas
- Não permitir inscrição pública

### RF04 - Gestão de Locais de Observação
- Criar pontos de observação (escola ou casa)
- Vincular a turmas e escolas
- Associar instrumentos (pluviômetro de referência por local)
- Códigos estáveis de instrumentos e versões de protocolo

### RF05 - Registro de Observações
- Criar observações por aluno em locais autorizados
- Fluxo: rascunho → enviado (pending) → aprovado (publish) ou devolvido (devolvido)
- Registros de:
  - Temperatura do ar (opcional)
  - Precipitação (mm) com início/fim da acumulação
  - Direção do vento (N, NE, L, SE, S, SO, O, NO, calmaria, variável)
  - Intensidade do vento (qualitativa)
  - Anemômetro de copos (volta, segundos, RPM calculado)
  - Barômetro de bexiga (deslocamento do ponteiro em mm)
  - Condição do céu (limpo, parcialmente nublado, etc.)
  - Gêneros de nuvens (10 da OMM)
  - Cobertura de nuvens (0-8 oitavos + valores especiais)
  - Extremos térmicos (Tmín, Tmáx)
  - Método (instrumento calibrado, artesanal, estimativa, visual)
  - Notas (texto breve)
- Validação: não permitir chuva negativa, datas impossíveis, períodos invertidos
- Detectar duplicatas por local/horário/variável
- Preservar histórico de instrumentos/protocolos

### RF06 - Revisão Docente
- Professores revisam observações de sua turma
- Aprovar ou devolver com comentário
- Reabrir observações aprovadas para correção
- Exportar dados da turma em CSV

### RF07 - Relatórios Didáticos (Atividades)
- Criar atividades por aluno
- Tipos: experimentos e previsão do tempo
- Para previsão: hipótese antes do início do intervalo, imutável após envio
- Confronto com observações reais e reflexão
- Fluxo: rascunho → enviado → aprovado ou devolvido

### RF08 - Biblioteca de Tutoriais
- Cadastrar tutoriais com 13 guias obrigatórios
- Categorias: instrumentos, experimentos, rotinas
- Conteúdo: objetivo, materiais, etapas, forma de leitura, unidade, limitações, cuidados, campos de coleta
- Versionamento de protocolos
- Professor cria rascunhos, administrador publica
- Tutoriais públicos acessíveis a visitantes

### RF09 - Consulta e Visualização
- Tabela paginada com filtros (período, local, variável)
- Gráficos (séries temporais por local/variável)
- Médias, mínimos, máximos, contagens
- Indicação de ausência de dados e cobertura
- Limites: não interpolar, não somar diferentes locais/instrumentos, não calcular média de direções

### RF10 - Exportação
- Exportação CSV dos dados aprovados (respeitando filtros)
- UTF-8 com cabeçalhos
- Neutralização de fórmulas em células textuais
- Não exportar nomes completos, credenciais ou localização residencial
- Arquivos não devem ficar em URL pública persistente

### RF11 - Interface
- Responsiva (prioridade celular)
- Formulários curtos com orientação junto aos campos
- Navegação por teclado
- Rótulos explícitos
- Contraste adequado
- Mensagens de erro específicas

### RF12 - Dados Fictícios
- Escola fictícia
- Duas turmas com professores distintos
- Alunos fictícios
- Pontos escolares e domésticos
- Registros de exemplo cobrindo chuva, rotações, barômetro, nuvens, extremos, experimento, previsão
- Identificar claramente como "DADOS FICTÍCIOS"

## Requisitos Não-Funcionais

### RNF01 - Performance
- Responsividade aceitável (até 2s em página comuns)
- Consultas paginadas (não carregar todos os dados)

### RNF02 - Segurança
- Nonce em todos os formulários e AJAX
- Validação no servidor para todos os dados
- Sanitização de entrada
- Prepared statements no banco
- Capabilities por objeto
- Sem REST API genérica para observações privadas

### RNF03 - Acessibilidade
- Navegação por teclado
- Rótulos explícitos
- Contraste adequado
- Não depender exclusivamente de cor

### RNF04 - Manutenibilidade
- Plugin separado do tema
- Código comentado apenas se solicitado
- Documentação no repositório
- Tests automatizados para lógica crítica

### RNF05 - Portabilidade
- WordPress 6.4.3 + BuddyPress 12.2.0
- PHP 8.1.x
- MariaDB 10.6.x
- Windows + Docker (desenvolvimento)
- Scripts PowerShell e shell Linux

## Limitações do MVP

- Sem Multisite
- Apenas localhost (127.0.0.1)
- Sem atualizações automáticas no ambiente local
- Sem PDF (apenas CSV e visualização no navegador)
- Sem gráficos interativos (apenas placeholders para Chart.js)
- Sem geolocalização ou mapas
- Sem sensores automáticos
- Sem previsão meteorológica operacional
- Sem integrações externas
- Sem certificados, rankings ou gamificação

## Requisitos de Integração

### BuddyPress
- Grupos como turmas (ocultos)
- Profiles mínimos
- API oficial (sem modificação de core)

### WordPress
- Custom Post Types: remc_escola, remc_local, remc_observacao, remc_tutorial, remc_atividade
- Taxonomias: remc_tutorial_cat, remc_metodo
- Hooks e filters para extensibilidade

## Requisitos de Banco de Dados

### Tabelas WordPress (sem tabelas próprias no MVP)
- wp_posts + wp_postmeta para CPTs
- wp_users + wp_usermeta para usuários
- wp_terms + wp_term_taxonomy + wp_term_relationships para taxonomias
- BuddyPress: wp_bp_groups + wp_bp_groups_groupmeta

### Metadados Principais
Ver dicionário-de-dados.md para lista completa.

## Requisitos de Interface BuddyPress

- Grupos do tipo `hidden`
- `invite_status = invitations_required`
- `hide_sitewide = 1`
- Sem inscrição pública, amizades ou mensagens privadas
- Apenas perfis mínimos (sem dados sensíveis)

## Requisitos de Testes

### Funcionais
- Ciclo aluno → envio → professor → aprovação → tabela/gráfico
- Aluno turma A não lê/altera dados da B
- Professor A não revisa turma B
- Professor não redefine senha de aluno vinculado a outro professor
- Visitante e usuário removido não acessam conteúdo privado
- Rascunhos/pendentes/devolvidos fora de consultas coletivas
- 0 mm permanece zero, campo vazio permanece ausente

### de Segurança
- XSS neutralizado
- CSRF bloqueado
- SQL Injection previsto
- Exportação CSV segura

## Requisitos de Documentação

- README.md: visão geral e instruções
- DESENVOLVIMENTO.md: comandos e versões
- RECONSTRUCTION.md: data da reconstrução
- CHANGELOG.md: alterações por etapa
- docs/requisitos.md (este arquivo)
- docs/arquitetura.md
- docs/dicionario-de-dados.md
- docs/catalogo-de-atividades.md
- docs/permissoes.md
- docs/versoes.md
- docs/testes.md
- docs/cronologia.csv
