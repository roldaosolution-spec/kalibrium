# Slice 002 — Multi-tenant Foundation

| Campo       | Valor                          |
|-------------|--------------------------------|
| **Status**  | Implementado                   |
| **Branch**  | `feat/slice-002`               |
| **Commit**  | `3aa5688`                      |
| **ADR**     | [ADR-0016](../adr/ADR-0016-multitenant.md) |
| **Parent**  | Slice 001 (commit `2ebb32c`)   |

---

## Objetivo

Estabelecer o isolamento multi-tenant em três camadas para todas as entidades da plataforma Kalibrium:

1. **Camada 1 — Eloquent (PHP):** `TenantScope` global scope + `HasTenant` trait + `TenantContext` em memória.
2. **Camada 2 — Middleware:** `SetTenantContext` resolve o tenant do usuário autenticado e popula o contexto PHP e o GUC do PostgreSQL.
3. **Camada 3 — PostgreSQL RLS:** política `tenant_isolation` com `FORCE ROW LEVEL SECURITY` na role `kalibrium_app`.

---

## Deliverables

| Artefato | Arquivo |
|---|---|
| Migração `tenants` | `database/migrations/0001_01_01_000004_create_tenants_table.php` |
| Migração RLS | `database/migrations/2026_04_19_000001_enable_tenant_rls.php` |
| Modelo `Tenant` | `app/Models/Tenant.php` |
| Trait `HasTenant` | `app/Models/Concerns/HasTenant.php` |
| Scope `TenantScope` | `app/Models/Scopes/TenantScope.php` |
| Context holder | `app/Support/TenantContext.php` |
| Middleware | `app/Http/Middleware/SetTenantContext.php` |
| Factory `TenantFactory` | `database/factories/TenantFactory.php` |
| Factory `UserFactory` | `database/factories/UserFactory.php` |
| Seeder `TenantSeeder` | `database/seeders/TenantSeeder.php` |
| Testes de isolamento | `tests/Feature/TenantIsolationTest.php` |
| Testes de middleware | `tests/Feature/SetTenantContextTest.php` |

---

## Critérios de Aceite

| ID | Critério | Status |
|----|---|---|
| AC-002-01 | Criação de registro auto-injeta `tenant_id` a partir do contexto | ✅ |
| AC-002-02 | Queries filtram automaticamente pelo tenant atual (Global Scope) | ✅ |
| AC-002-03 | SQL bruto com `tenant_id` errado é bloqueado pelo RLS (como `kalibrium_app`) | ✅ |
| AC-002-04 | Tenant A não consegue ver dados do Tenant B via Eloquent | ✅ |
| AC-002-05 | Política RLS ativa após migration em CI com PostgreSQL | ✅ |
| F4-IDOR | Acesso cross-tenant por ID direto (IDOR) bloqueado em `User::find`, `User::where`, HTTP GET | ✅ |

---

## Decisões de Design

### GUC session-level via `set_config()`

O middleware usa `set_config('app.current_tenant_id', $id, false)` em vez de `SET LOCAL`. `SET LOCAL` é escopo da transação implícita do autocommit e é perdido antes de qualquer query real executar. `set_config` com `is_local=false` persiste para toda a conexão.

O bloco `finally` do middleware limpa o GUC (`set_config(guc, '', false)`) para evitar vazamento em conexões pooladas.

### `FORCE ROW LEVEL SECURITY`

A migração aplica `ALTER TABLE users FORCE ROW LEVEL SECURITY` para que o dono da tabela (não superuser) também seja sujeito ao RLS. PostgreSQL superusers (`kalibrium`) sempre bypassam RLS; a role `kalibrium_app` não tem BYPASSRLS.

### Restrição de PgBouncer

O middleware usa `set_config()` com escopo de sessão. PgBouncer **deve** ser configurado em modo `session` (não `transaction`), para que o GUC persista durante toda a requisição. Ver ADR-0016.

---

## Itens Diferidos (fora do escopo do Slice 002)

| Item | Motivo | Slice |
|---|---|---|
| `password_reset_tokens` — falta `tenant_id` + RLS | Pertence ao fluxo de autenticação | Slice 003 |
| `sessions` — falta política RLS por `user_id`→tenant | Pertence ao fluxo de autenticação | Slice 003 |
| RBAC — campo `role` sem Gates/Policies | Pertence ao módulo de autorização | Slice 003 |
| LGPD — campos de consentimento em `users` | Escopo de privacidade/LGPD | Slice 004 |
| `SetTenantContext` no grupo `web` | Não há rotas web com modelos tenant-scoped por ora | Quando necessário |

---

## Riscos Conhecidos

- **Conexões pooladas (PgBouncer transaction mode):** Quebra o isolamento de GUC. Requer configuração correta de infra.
- **Superuser bypass:** CI roda como `kalibrium` (superuser). Testes RLS explicitamente fazem `SET LOCAL ROLE kalibrium_app` para validar a política.
- **Fila de jobs:** `TenantContext` estático não é resetado entre jobs. Mitigação: `TenantContext::clear()` no setup de cada job tenant-aware (a ser implementado quando jobs forem adicionados).
