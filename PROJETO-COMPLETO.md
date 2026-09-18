# REMC — Projeto Completo

## Reconstrução Concluída ✓

**Data:** 17/09/2026  
**Versão:** 0.1.0 (MVP Inicial)  
**Tecnologia:** WordPress 6.4.3 + BuddyPress 12.2.0

## Estado Final

### Plugin REMC Core (10 arquivos)
- `remc-core.php` - Bootstrap
- `class-remc-post-types.php` - CPTs (5 tipos)
- `class-remc-roles-capabilities.php` - Permissões (4 papéis)
- `class-remc-validation.php` - Validação de dados
- `class-remc-notifications.php` - Notificações
- `class-remc-reports.php` - Relatórios e CSV
- `class-remc-tutorials.php` - Gerenciamento de tutoriais
- `class-remc-meta-boxes.php` - Metaboxes para formulários
- `class-remc-bootstrap.php` - Bootstrap data
- `class-remc-bootstrap-command.php` - WP-CLI command

### Tema REMC Educacional (9 arquivos)
- `functions.php` - Configurações
- `style.css` - Estilos principais
- `index.php` - Template principal
- `front-page.php` - Página inicial
- `template-student-dashboard.php` - Painel aluno
- `template-teacher-dashboard.php` - Painel professor
- `template-tutorial.php` - Página de tutorial
- `scripts.js` - JavaScript
- `charts.css`, `forms.css` - Estilos auxiliares

### Documentação (9 arquivos)
- `requisitos.md` - Requisitos funcionais
- `arquitetura.md` - Arquitetura técnica
- `dicionario-de-dados.md` - Dicionário de dados
- `catalogo-de-atividades.md` - 13 tutoriais
- `permissoes.md` - Sistema de permissões
- `versoes.md` - Versões tecnológicas
- `testes.md` - Testes automatizados
- `deploy.md` - Deployment
- `cronologia.csv` - Cronologia

### Scripts (2 arquivos)
- `setup.sh` - Setup Linux/Mac
- `bootstrap.ps1` - Bootstrap Windows

## Como Rodar

### Docker (Recomendado)
```bash
docker-compose up -d
```

### PHP Built-in
```bash
cd wp-core
php -S 127.0.0.1:8000
```

### XAMPP/WAMP
1. Copiar `wp-core` para `htdocs/remc`
2. Criar banco `remc_db`
3. Acessar http://localhost/remc

## Acessos

- **Admin:** `admin_remc`
- **Professores:** `professor_a` (Turma A), `professor_b` (Turma B)
- **Alunos:** `aluno_joao`, `aluno_maria` (Turma A), `aluno_pedro` (Turma B)

Senhas: geradas pelo bootstrap e exibidas uma única vez na execução (não
versionadas). Veja `DESENVOLVIMENTO.md`.

## 13 Tutoriais

**Instrumentos (3):** Pluviômetro PET, Anemômetro Copos, Barômetro Bexiga  
**Experimentos (3):** Nuvem na Garrafa, Mini Ciclo Água, Duas Vasilhas  
**Rotinas (7):** Pluviométrico, Pressão, RPM, Nuvens, Rosa Ventos, Amplitude, Previsão

## Próximos Passos

- Testes automatizados (PHPUnit, Cypress)
- Gráficos interativos (Chart.js)
- Bootstrap idempotente

---

**Uso interno educacional.**
