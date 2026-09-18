# RECONSTRUCTION.md

## Dados da reconstrução

- **Data real da reconstrução:** 2026-09-17
- **Corte tecnológico:** 31/01/2024 (WordPress 6.4.3 e BuddyPress 12.2.0)
- **Origem funcional:** Documento de referência de julho de 2025, recuperado de documentação posterior

## Ferramentas utilizadas

- Docker Compose + WSL 2 (ambiente local reproduzível)
- WP-CLI (instalação, ativação, bootstrap de dados)
- WordPress 6.4.3 + BuddyPress 12.2.0 (versões fixadas na reconstrução)
- Scripts PowerShell (Windows) e shell Linux (dentro dos contêineres)
- Git (versionamento e histórico)

## Histórico de commits

Ver `docs/cronologia.csv` para registro por etapa.

O repositório tem, nesta ordem:

1. `a52778c` — Reconstrução do MVP REMC sobre **WordPress 6.4.3 + BuddyPress 12.2.0**
   (estado histórico de 31/01/2024, preservado).
2. Commit de modernização (17/09/2026) — **WordPress 7.1 + BuddyPress 14.5.2 + PHP 8.3**,
   por decisão do responsável. Registrado em `docs/versoes.md`, seção
   "Modernização da stack".

A modernização não reescreve a reconstrução: o commit histórico continua
recuperável (por exemplo, com `git checkout a52778c`).

## Observações

- Este código foi reconstruído a partir de descrições funcionais, **não** de código original.
- As versões históricas (WordPress, BuddyPress, PHP) são mantidas no commit da
  reconstrução; o ambiente atual usa a stack modernizada.
- Dados fictícios são claramente identificados. Não usar em produção.
- Para implantação pública, atualizar e revalidar todas as dependências.
- Datas de autoria, de aplicação do commit e de envio ao servidor são coisas
  diferentes: metadados retroativos não recuperam um repositório perdido nem
  comprovam que este código existia em 2024.
