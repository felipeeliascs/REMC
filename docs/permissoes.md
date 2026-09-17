# Documentação - Permissões REMC

> **Modelo efetivo:** a autorização por objeto é aplicada no filtro
> `map_meta_cap` (`Remc_Roles_Capabilities::map_meta_cap`), devolvendo
> `do_not_allow` quando o vínculo com a escola/turma não confere. Papéis e
> capabilities são criados por `Remc_Roles_Capabilities::register_caps()`
> (`add_role()`), incluindo um mapa explícito de capabilities por CPT.

## Papéis

| Papel | Criado por | Resumo |
|-------|-----------|--------|
| `administrator` | WordPress | Recebe todas as capabilities da REMC |
| `professor` | remc-core (`add_role`) | Revisa turmas sob sua responsabilidade |
| `aluno` | remc-core (`add_role`) | Registra e consulta dados da própria turma |
| `visitante` | remc-core (`add_role`) | Somente `read` + tutoriais públicos |

## Capabilities (por CPT, via mapa explícito)

Os CPTs não usam a pluralização automática do WordPress. O mapa é:

| CPT | exemplo de capabilities |
|-----|--------------------------|
| `remc_observacao` | `create_observacao`, `edit_observacao`, `edit_observacoes`, `edit_published_observacoes`, `publish_observacao`, `read_private_observacoes` |
| `remc_atividade` | `create_atividade`, `edit_atividade`, `edit_atividades` |
| `remc_local` | `create_local`, `edit_local`, `edit_locais` |
| `remc_escola` | `create_escola`, `edit_escola`, `edit_escolas` |
| `remc_tutorial` | `create_tutorial`, `edit_tutorial`, `edit_tutoriais`, `publish_tutorial` |

## Autorização por objeto (map_meta_cap)

### Observação (`remc_observacao`)

| Situação | edit | read | delete |
|----------|------|------|--------|
| Administrador | ✅ | ✅ | ✅ |
| Autor, observação aberta (rascunho/devolvido) | ✅ | ✅ | ✅ |
| Autor, observação aprovada | ❌ (reabrir antes) | ✅ | ❌ |
| Professor responsável pela turma | ✅ | ✅ | ❌ (só admin) |
| Professor de outra turma | ❌ | ❌ | ❌ |
| Colega da mesma turma | ❌ | ✅ (apenas aprovadas) | ❌ |
| Aluno de outra turma | ❌ | ❌ | ❌ |

### Atividade (`remc_atividade`)
- Autor: acesso total ao próprio registro.
- Professor responsável pela turma: `read` e `edit` (revisão).
- Demais: negado.

### Local e Escola
- Professor: gerencia locais das turmas que administra; escola à qual está
  vinculado (`_linked_escola`).
- Aluno: lê locais da própria turma.
- Demais: negado.

## Vínculo professor ↔ turma

`managed_turmas( $user_id )` considera:
1. `usermeta._linked_turmas`; e
2. grupos cujo `groupmeta professor_responsavel` seja o usuário.

Assim, um papel global de professor **não** dá acesso a todas as turmas.

## Validação no salvamento

`validate_scope()` remove o vínculo `_turma` quando o usuário tenta registrar
uma observação/atividade em turma da qual não participa (aluno) nem administra
(professor).

## Controle de senhas

- Professores só podem redefinir senhas de alunos vinculados exclusivamente às
  turmas sob sua responsabilidade.
- Aluno em turmas de professores diferentes: redefinição apenas pelo
  administrador.
- Senhas iniciais são temporárias, com troca no primeiro acesso.

## Bloqueio de previsão

- Hipótese imutável após envio; aprovação posterior não libera edição.
- Versões criadas após o início do intervalo previsto não valem como
  previsão antecipada.

## Feed social (compartilhamento de dados)

- **Compartilhar:** apenas a autora ou o autor da observação, somente se
  **aprovada** e se o aluno for **membro da turma** dona do registro.
- **Descompartilhar:** a autora ou o autor, a qualquer momento.
- **Ler (visitante deslogado):** apenas itens do componente `remc`
  (`remc_shared_observation`); não vê itens de membros/grupos.
- **Comentar e curtir:** apenas **membros da turma** daquela observação.
- **Não compartilhável:** rascunhos, pendentes e devolvidas.
- **Reabertura/devolução:** o item sai do feed; nova publicação exige novo opt-in.
- **Nunca exposto:** notas, e-mail, nome completo e endereço residencial.

## Segurança de dados

- Não usar REST genérica para observações privadas.
- Nonce em formulários e AJAX; nonce **não** substitui autorização por objeto.
- Páginas privadas não devem ser servidas por cache compartilhado.
- Exportações CSV não ficam em URL pública persistente.

## Auditoria

- Cada revisão guarda autoria, datas e status anterior
- Comentários de devolução são armazenados em metadados da revisão
- Alterações em instrumentos/protocolos preservam histórico das medições anteriores
