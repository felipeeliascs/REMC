# Documentação - Testes REMC

## Tipo de Testes

### 1. Testes de Autorização (Unit/Integration)
Verificam que usuários só acessam dados autorizados.

**Casos de teste:**
- Aluno não lê observações de outra turma (troca de ID em URL)
- Professor não revisa turma de outro professor
- Professor não redefine senha de aluno vinculado a outro professor
- Visitante não acessa páginas privadas
- Usuário removido perde acesso instantaneamente

**Ferramentas:**
- PHPUnit com `WP_UnitTestCase`
- Testes de capability mapping

### 2. Testes de Validação (Unit)
Verificam regras de validação de dados.

**Casos de teste:**
- Temperatura fora de faixa (-90°C a +60°C)
- Precipitação negativa
- Anemômetro com duração zero
- Barômetro com valor inválido
- Direção do vento inválida
- Cobertura de nuvens inválida
- Período invertido (fim antes de início)
- Duplicata por local/horário/variável

**Ferramentas:**
- PHPUnit
- `Remc_Validation` diretamente

### 3. Testes de Cálculo (Unit)
Verificam fórmulas de agregação e métricas.

**Casos de teste:**
- RPM = 60 × voltas / segundos (15 voltas/30s = 30 RPM)
- Amplitude = Tmáx - Tmín (27°C - 18°C = 9°C)
- Média com lacunas (não interpoladas)
- Precipitação por mesmo local/instrumento (não somar diferentes)
- Direção do vento (sem média aritmética)

**Ferramentas:**
- PHPUnit

### 4. Testes de Integração (Integration)
Verificam fluxos completos no navegador.

**Casos de teste:**
- ciclo real: aluno envia → professor aprova → dados aparecem em tabela
- ciclo completo previsão → observação → confronto
- bootstrap idempotente (segunda execução sem duplicação)
- BuddyPress desativado (aviso sem erro fatal)
- Alternância de tema (dados continuam funcionando)

**Ferramentas:**
- Cypress (recomendado) ou testes manuais
- WP CLI para setup

### 5. Testes de Segurança (Integration)
Verificam proteções contra ataques.

**Casos de teste:**
- XSS em campos de texto (scripts são neutralizados)
- CSRF (nonce inválido é rejeitado)
- SQL Injection (prepared statements)
- Exportação CSV (fórmulas neutralizadas)
- AJAX sem autorização (403)

**Ferramentas:**
- PHPUnit com testes de input malicioso

## Como Executar

### Testes Unitários (PHPUnit)

```bash
# Dentro do contêiner
wp package install wp-phpunit/wp-phpunit --allow-root
cd /var/www/html/wp-content/plugins/remc-core

# Executar testes
phpunit tests/
```

### Testes de Integração (Cypress)

```bash
# Instalar
npm install cypress --save-dev

# Executar
npx cypress run
npx cypress open
```

### Teste Manual (Navegador)

1. Iniciar ambiente: `docker-compose up -d`
2. Instalar WordPress: ver `DESENVOLVIMENTO.md`
3. Criar dados: `wp remc bootstrap --allow-root`
4. Acessar `http://127.0.0.1`
5. Testar fluxos completos com diferentes perfis

## Tabela de Testes

| Teste | Tipo | Status | Observações |
|-------|------|--------|-------------|
| Aluno não lê dados de turma B | Autorização | Pendente | Usar `user_has_cap` filter |
| Professor não revisa turma B | Autorização | Pendente | Verificar `linked_turmas` |
| RPM calculado corretamente | Cálculo | Pendente | `Remc_Validation::validate_anemometer()` |
| Amplitude Tmáx - Tmín | Cálculo | Pendente | Validar Tmáx >= Tmín |
| Precipitação não soma diferentes locais | Cálculo | Pendente | Filtrar por `_local_id` |
| XSS neutralizado | Segurança | Pendente | Sanitizar `wp_kses_post()` |
| CSRF bloqueado | Segurança | Pendente | Verificar `wp_verify_nonce()` |
| Exportação CSV | Integração | Pendente | Verificar `remc_export_csv` AJAX |

## Dados de Teste

### Usuários
- `admin`: administrador
- `professor_exemplo`: professor (Turma A e B)
- `aluno_joao`: aluno (Turma A)
- `aluno_maria`: aluno (Turma B)

### Escolas
- ID 1: Escola Exemplo

### Turmas
- ID 1: Turma A - 5º Ano
- ID 2: Turma B - 6º Ano

### Locais
- ID 1: Ponto Escola - Turma A
- ID 2: Ponto Casa - Turma A
- ID 3: Ponto Escola - Turma B
- ID 4: Ponto Casa - Turma B

## Regras de Qualidade

- 100% de cobertura para `Remc_Validation`
- 80% mínimo para `Remc_Roles_Capabilities`
- Todos os fluxos principais testados manualmente
- Testes devem ser idempotentes e não depender de estado externo

## Bugs Conhecidos

Nenhum bug conhecido na versão inicial.
