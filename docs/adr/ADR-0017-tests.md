# ADR-0017 — Testes com Rastreabilidade AC-ID Obrigatória

| Campo       | Valor                                              |
|-------------|----------------------------------------------------|
| **Status**  | Proposta                                           |
| **Data**    | 2026-04-19                                         |
| **Autores** | CEO (IA), Equipe de QA                             |
| **Revisores** | Painel de Auditores (10 especialistas)           |

---

## Contexto

O Kalibrium opera em domínio regulatório (ISO 17025 / RBC / LGPD). Auditores do Inmetro/Cgcre podem exigir rastreabilidade entre **requisito → critério de aceite → teste automatizado → resultado**. Sem rastreabilidade, um bug em dual sign-off ou isolamento de tenant pode não ser detectado antes de produção.

Adicionalmente, o processo de desenvolvimento usa um **painel de 10 auditores especialistas** que revisam cada slice antes do merge. Esses auditores precisam verificar facilmente quais ACs foram testadas.

## Decisão

### 1. Nomenclatura obrigatória de testes (AC-ID Traceability)

Todo teste que valida um critério de aceite **DEVE** referenciar o AC-ID no nome:

```php
// Formato: [AC-XXX-YY] descrição do comportamento testado
it('[AC-001-01] artisan serve inicia sem erros de configuração', function () { ... });
it('[AC-003-05] técnico sem habilitação vigente é bloqueado ao iniciar calibração', function () { ... });
it('[AC-003-06] padrão vencido não pode ser selecionado mesmo offline', function () { ... });
```

Regras:
- `AC-XXX` = número do slice (001, 002, etc.)
- `-YY` = número sequencial do critério dentro do slice
- Testes de unidade pura (sem AC direto) usam prefixo `[UNIT]`
- Testes de regressão usam prefixo `[REGR-XXX]`

### 2. Cobertura mínima por domínio

| Domínio | Cobertura mínima |
|---|---|
| Isolamento de tenant (`TenantScope` + RLS) | 100% |
| Dual sign-off de certificado | 100% |
| Competência de técnico (habilitação) | 100% |
| Validade de padrão | 100% |
| Cálculo de incerteza | 100% |
| NFS-e geração | 90% |
| Restante da aplicação | 80% |

### 3. Ferramentas

| Ferramenta | Uso |
|---|---|
| **Pest 4** | Testes unitários e de feature (principal) |
| **Pest Browser Testing** | Testes de UI Livewire |
| **Playwright** | E2E críticos (fluxo completo de calibração, emissão NFS-e) |
| **Factories + Faker PT-BR** | Dados de teste realistas em português |

### 4. Banco de dados em testes

- Testes de Feature rodam contra **PostgreSQL real** (Docker no CI e localmente)
- Proibido usar SQLite em testes — PostgreSQL RLS não funciona em SQLite
- `RefreshDatabase` + `WithFaker` são o padrão
- Factories criam tenant isolado por teste (sem contaminação)

### 5. Relatório de cobertura

- `pest --coverage --min=80` no CI bloqueia merge se cobertura global cair abaixo de 80%
- Relatório HTML em `storage/coverage/` gerado no CI e publicado como artefato
- Cobertura por domínio crítico é verificada por script auxiliar `scripts/check-critical-coverage.php`

### 6. Nomenclatura de datasets de teste

```php
dataset('técnicos sem habilitação', [
    'habilitação vencida'    => [fn () => TechnicianFactory::withExpiredQualification()],
    'habilitação inexistente' => [fn () => TechnicianFactory::withoutQualification()],
    'habilitação suspensa'   => [fn () => TechnicianFactory::withSuspendedQualification()],
]);
```

## Consequências

### Positivas
- Rastreabilidade direta AC → teste → resultado facilita auditorias RBC/ISO 17025
- Testes contra PostgreSQL real detectam problemas de RLS antes do deploy
- Cobertura mínima por domínio crítico garante que regressões em dual sign-off ou tenant isolation sejam detectadas no CI

### Negativas / Riscos
- Exigir PostgreSQL no ambiente de desenvolvimento local aumenta o setup inicial — mitigado com `docker-compose.yml` pré-configurado
- Testes E2E com Playwright são lentos — executados apenas no CI em push para `main`, não em feature branches

### Alternativas rejeitadas
- **SQLite em testes** — rejeitado: não suporta RLS, comportamento diferente de PG em JSONB e full-text search
- **Nomear testes sem AC-ID** — rejeitado: inviabiliza rastreabilidade para auditores regulatórios
- **Mock do banco em tests de feature** — rejeitado: ADR-0016 depende de RLS real, não pode ser mockado
