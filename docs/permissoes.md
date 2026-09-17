# Documentação - Permissões REMC

## Papéis e Capabilities

### Administrador
- Gerenciar escolas, professores e turmas
- Publicar tutoriais
- Aprovar/reviver observações de qualquer turma
- Exportar dados de todas as turmas

**Capabilities:**
- `read`, `edit_posts`, `delete_posts`
- `manage_escolas`, `manage_turmas`, `manage_alunos`
- `reset_student_password`
- `export_data`

### Professor
- Criar e gerenciar turmas apenas nas escolas vinculadas
- Aprovar/devolver observações de sua turma
- Ver e exportar dados de sua turma
- Editar tutoriais próprios
- **Não pode** redefinir senhas de alunos também vinculados a outros professores

**Capabilities:**
- `read`, `edit_posts`, `delete_posts`
- `read_escola`, `edit_escola` (vinculada à escola)
- `read_local`, `edit_local`, `create_local` (vinculada à turma)
- `read_observacao`, `edit_observacao`, `create_observacao`, `publish_observacao`, `approve_observacao` (vinculada à turma)
- `read_atividade`, `edit_atividade`, `create_atividade` (vinculada à turma)
- `export_data` (vinculada à turma)

### Aluno
- Criar observações em locais autorizados de sua turma
- Criar atividades
- Ver dados de sua turma (apenas aprovados)
- Editar apenas seus próprios registros
- **Não pode** ver/alterar dados de outros alunos
- **Não pode** publicar tutoriais ou fazer upload de arquivos

**Capabilities:**
- `read`, `edit_posts`
- `read_observacao`, `edit_observacao`, `create_observacao`
- `read_atividade`, `edit_atividade`, `create_atividade`

### Visitante
- Apenas acesso público a tutoriais publicados
- **Não pode** acessar alunos, perfis, turmas ou registros individuais

**Capabilities:**
- `read` (apenas para conteúdo público)

## Regras de Autorização por Objeto

### Escola
- Administradores têm acesso total
- Professores só acessam escolas vinculadas
- Vínculo: `usermeta._linked_escola` → `post_id`

### Local
- Administradores têm acesso total
- Professores só acessam locais de turmas sob sua responsabilidade
- Alunos só visualizam locais de sua turma
- Vínculo: `postmeta._turma` → `group_id` → `usermeta._linked_turmas`

### Observação
- Administradores têm acesso total
- Alunos só acessam suas próprias observações
- Professores só acessam observações de sua turma
- Aluno pode editar apenas rascunhos/drafts próprios
- Professor pode aprovar/devolver observações de sua turma
- Vínculo: `postmeta._turma` → `group_id`

### Atividade
- Administradores têm acesso total
- Alunos só acessam suas próprias atividades
- Professores só acessam atividades de sua turma
- Vínculo: `postmeta._turma` → `group_id`

### Tutorial
- Todos podem ler tutoriais publicados
- Professores e administradores podem criar/editar
- Administradores publicam
- Aluno não pode publicar

## Controle de Senhas

- Professores só podem redefinir senhas de alunos **exclusivamente** vinculados a suas turmas
- Se um aluno está em turmas de múltiplos professores, apenas administrador pode redefinir a senha
- Senhas iniciais são temporárias, exigindo troca no primeiro login

## Bloqueio de Previsão

- Hipótese de previsão não pode ser editada após envio
- Aprovação docente posterior não libera edição da hipótese original
- Versões criadas após início do intervalo previsto não são aceitas como antecipadas

## Bloqueio Temporal

- Observações aprovadas não aparecem em consultas se foram reabertas para correção
- Rascunhos, pendentes e devolvidas não aparecem em gráficos ou agregações
- Somente observações `publish` são contabilizadas em séries históricas

## Segurança de Dados

- Não usar REST API genérica para observações privadas
- Nonce obrigatório em todos os formulários e AJAX
- Validação de autorização por objeto em todas as operações
- Páginas privadas não devem ser servidas por cache compartilhado
- Exportações CSV não devem ficar em URLs públicas persistentes

## Feed social (compartilhamento de dados)

- **Compartilhar:** apenas a autora ou o autor da observação, somente se estiver
  **aprovada** e se o aluno for **membro da turma** dona do registro.
- **Descompartilhar:** a autora ou o autor, a qualquer momento.
- **Ler (visitante deslogado):** apenas itens do componente `remc`
  (`remc_shared_observation`); não vê itens de membros/grupos.
- **Ler (aluno/professor logado):** vê os itens compartilhados; o conteúdo da
  turma continua restrito pelo vínculo de grupo.
- **Comentar e curtir:** apenas **membros da turma** daquela observação.
  Visitante e alunos de outras turmas não interagem.
- **Não compartilhável:** rascunhos, pendentes e devolvidas.
- **Reabertura/devolução:** o item sai do feed; nova publicação exige novo opt-in.
- **Nunca exposto:** notas, e-mail, nome completo, endereço residencial e o
  estado de revisão de registros não aprovados.

## Auditoria

- Cada revisão guarda autoria, datas e status anterior
- Comentários de devolução são armazenados em metadados da revisão
- Alterações em instrumentos/protocolos preservam histórico das medições anteriores
