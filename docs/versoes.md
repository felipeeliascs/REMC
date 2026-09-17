# Documentação - Versões da REMC

> **Importante:** este documento descreve duas configurações.
> A **reconstrução histórica** (WordPress 6.4.3 + BuddyPress 12.2.0) é o corte
> original de 31/01/2024 e continua preservada no primeiro commit do repositório.
> A **modernização** (WordPress 7.1 + BuddyPress 14.5.2 + PHP 8.3) foi aplicada
> depois, por decisão do responsável, e está registrada na seção
> "Modernização da stack". As duas não são equivalentes.

## 1. Versões da reconstrução histórica (corte 31/01/2024)

| Componente | Versão | Data de Lançamento | Notas |
|------------|--------|-------------------|-------|
| WordPress | 6.4.3 | 30/01/2024 | Versão específica para MVP |
| BuddyPress | 12.2.0 | 23/01/2024 | Versão específica para MVP |
| PHP | 8.1.x | - | Linha 8.1, compatível com WP 6.4.3 |
| MariaDB | 10.6.x | - | Linha 10.6, compatível com WP 6.4.3 |


## Data de Corte

**31/01/2024** - suposição para "início de 2024". Este corte define:
- Versões máximas aceitas de plugins e dependências
- APIs disponíveis para uso (sem recursos do WordPress 6.5+)

## Ambiente de Desenvolvimento

| Componente | Versão | Onde |
|------------|--------|------|
| Docker Desktop | 4.91.0 | Host Windows 11 Pro (build 26200) |
| Docker Engine | 29.8.0 | Host |
| WSL | 2.7.14 | Backend do Docker (kernel 6.18) |
| WordPress | 6.4.3 | Contêiner `remc-wp` (verificado) |
| BuddyPress | 12.2.0 | Plugin no contêiner (verificado) |
| PHP | 8.1.34 | Contêiner `remc-wp` (imagem `wordpress:6.4.3-php8.1`) |
| MariaDB | 10.6.16 | Contêiner `remc-mariadb` (imagem `mariadb:10.6.16`) |

### Verificação executada (2026-09-17)

```
wp core version          -> 6.4.3
wp eval 'BP_VERSION'     -> 12.2.0
php -v (container)       -> 8.1.34
mariadb --version        -> 10.6.16-MariaDB
HTTP http://127.0.0.1/   -> 200 OK
```

### Notas de integridade

- As imagens foram obtidas do Docker Hub (`wordpress:6.4.3-php8.1`, `wordpress:cli-php8.1`, `mariadb:10.6.16`).
- O check de integridade disponível é o do próprio Docker (digests das camadas das imagens oficiais do Docker Hub), não uma assinatura criptográfica reproduzida de 2024.
- O BuddyPress 12.2.0 foi obtido de `https://downloads.wordpress.org/plugin/buddypress.12.2.0.zip` (distribuição oficial).
- As imagens foram **construídas/baixadas hoje**, não é uma imagem de 2024; apenas o conteúdo do aplicativo (WP/BP) corresponde ao corte.

### APIs do BuddyPress confirmadas na versão 12.2.0

Funções efetivamente usadas e testadas neste ambiente:

- `groups_create_group()`
- `groups_get_id()` (a antiga `groups_get_group_by()` **não existe** em 12.2.0)
- `groups_edit_group_settings()`
- `groups_update_groupmeta()`
- `groups_join_group()`
- `groups_promote_member()` (a antiga `groups_promote_user()` **não existe** em 12.2.0)

Componentes ativados: `core`, `members`, `xprofile`, `settings`, `groups`.
Componentes desativados (fora do MVP): `activity`, `friends`, `messages`, `blogs`, `notifications`.

## Compatibilidade de Plugins

### Plugins Obrigatórios
- BuddyPress 12.2.0 (oficial, não multisite)

### Plugins Não Incluídos no MVP
- Sem plugins de terceiros obrigatórios
- Sem multilíngue (apenas pt_BR)
- Sem cache avançado (apenas cache interno do WP)

## Plugins de Terceiros (Opcional para Extensões Futuras)

| Plugin | Versão | Licença | Finalidade |
|--------|--------|---------|------------|
| Chart.js | 4.x | MIT | Gráficos no navegador |
| PHPUnit | 9.x | GPL-2.0+ | Testes PHP |
| WP-CLI | 2.x | GPL-2.0+ | Administração via CLI |

## Notas de Versão

### 0.1.0 (MVP Inicial)
- Plugin `remc-core` com CPTs básicos
- Tema `remc-educacional` simples
- Integração com BuddyPress 12.2.0
- 13 guias didáticos
- Workflow de observação e revisão
- Exportação CSV básica

### Próximas Versões (Planejado)
- 0.2.0: Gráficos interativos com Chart.js
- 0.3.0: Testes automatizados
- 0.4.0: Suporte a múltiplos idiomas
- 1.0.0: Versão estável para produção

## Fontes de Verificação

- [WordPress 6.4.3 Release Notes](https://wordpress.org/documentation/wordpress-version/version-6-4-3/)
- [BuddyPress 12.2.0 Release](https://buddypress.org/2024/01/buddypress-12-2-0-maintenance-release/)
- [WordPress Releases](https://wordpress.org/download/releases/)
- [PHP 8.1 Compatibility](https://www.php.net/releases/8.1/)
- [MariaDB 10.6 Changelog](https://mariadb.com/kb/en/mariadb-10-6-release-notes/)

## Atualizações

⚠️ **Importante**: Este ambiente mantém versões fixas para reprodução histórica. Em produção:
1. Atualizar WordPress, BuddyPress e PHP regularmente
2. Revalidar compatibilidade
3. Testar cuidadosamente em staging
4. Realizar backup antes de atualizações

## 2. Modernização da stack

Aplicada em **17/09/2026**, por decisão do responsável, mantendo o histórico
anterior no primeiro commit da reconstrução. O estado histórico não foi apagado:
ele permanece recuperável pelo commit `e0019b5`.

| Componente | Antes (histórico) | Depois (moderno) | Observação |
|------------|-------------------|------------------|------------|
| WordPress | 6.4.3 | **7.1** | Core atualizado no volume e imagem fixada em `wordpress:7.1-php8.3-apache` |
| BuddyPress | 12.2.0 | **14.5.2** | Requer WP 6.1+; testado até 7.0.4 |
| PHP | 8.1.34 | **8.3.33** | Imagem `wordpress:7.1-php8.3-apache` / CLI `wordpress:cli-php8.3` |
| MariaDB | 10.6.16 | 10.6.16 (mantido) | Atende ao requisito do BP (10.4+); sem migração de dados |
| Docker Desktop | 4.91.0 | 4.91.0 | Ferramenta atual |
| WSL | 2.7.14 | 2.7.14 | Backend do Docker |

### Motivo

O responsável atualizou o WordPress para 7.1. Com o BuddyPress 12.2.0 em um
WordPress ≥ 6.7, surgia o aviso:

```
PHP Notice: Function _load_textdomain_just_in_time was called incorrectly.
Translation loading for the "buddypress" domain was triggered too early.
(This message was added in version 6.7.0.)
```

Problema de compatibilidade do BuddyPress 12.2.0 (jan/2024) com WordPress ≥ 6.7,
não do código da REMC. A correção escolhida foi modernizar o BuddyPress junto
com o WordPress.

### Verificação executada após a modernização (17/09/2026)

```
wp core version         -> 7.1
wp plugin get buddypress--field=version -> 14.5.2
php -v (container)      -> 8.3.33
HTTP http://127.0.0.1/  -> 200 OK
debug.log               -> sem avisos/erros
bootstrap (2a execucao) -> sem duplicacao (1 escola, 4 locais, 5 observacoes,
                           13 tutoriais, 2 atividades, 6 usuarios)
grupos                  -> Turma A e Turma B com status hidden
componentes BP          -> core, members, xprofile, settings, groups
                           (activity, notifications, friends, messages,
                           blogs desativados)
validacoes              -> 15/30s = 30 RPM; duracao 0 recusada; 0 mm = 0;
                           "23,5" -> 23.5; direcao "NO" preservada
```

### Diferenças de comportamento observadas

- BuddyPress 14.x ativa `activity` e `notifications` por padrão; ambos foram
  desativados para manter o requisito do MVP (feed de atividades desativado).
- Os nomes das APIs de grupos usados pelo `remc-core` (`groups_create_group`,
  `groups_get_id`, `groups_edit_group_settings`, `groups_update_groupmeta`,
  `groups_join_group`, `groups_promote_member`) permanecem válidos no BP 14.x.
- Não foi usada nenhuma API introduzida depois do corte no código do produto;
  a modernização é apenas de ambiente.

### O que não foi modernizado

- MariaDB mantido em 10.6.16 (sem risco de migração e compatível com o BP 14.x).
- Sem Multisite, sem dependências pagas, sem frameworks.

