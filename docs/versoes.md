# Documentação - Versões da REMC

## Versões Fixadas

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
