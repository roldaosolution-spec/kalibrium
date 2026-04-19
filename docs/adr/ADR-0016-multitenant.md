# ADR-0016 — Isolamento Multi-Tenant: Row-Level `tenant_id` + Global Scope + PostgreSQL RLS

| Campo       | Valor                                              |
|-------------|----------------------------------------------------|
| **Status**  | Aceita                                             |
| **Data**    | 2026-04-19                                         |
| **Autores** | CEO (IA), Equipe de Arquitetura                    |
| **Revisores** | Painel de Auditores                              |

---

## Contexto

O Kalibrium é uma plataforma SaaS multi-tenant. Vazamento de dados entre tenants é **incidente crítico de segurança (S1)** — invalida conformidade LGPD, ISO 17025 e RBC. O MVP precisa suportar 50+ tenants simultâneos com isolamento total e auditável.

Existem três estratégias clássicas de multi-tenancy:
1. **Banco separado por tenant** — isolamento máximo, custo de infraestrutura alto
2. **Schema separado por tenant** — isolamento bom, complexidade de migration alta
3. **Single DB + `tenant_id`** — mais econômico, requer defesa em profundidade

## Decisão

Adotar **Single Database com defesa em três camadas**:

### Camada 1 — `tenant_id` em todas as tabelas
- Todas as tabelas de negócio recebem coluna `tenant_id UUID NOT NULL REFERENCES tenants(id)`
- Índice composto `(tenant_id, id)` como chave primária lógica em tabelas críticas
- `tenant_id` é imutável após criação do registro

### Camada 2 — Eloquent Global Scope (`TenantScope`)
```php
// Automaticamente injetado em todas as queries de modelos que usam HasTenant
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable().'.tenant_id', currentTenantId());
    }
}
```
- Todos os Models de negócio usam o trait `HasTenant` que registra `TenantScope`
- `withoutGlobalScope(TenantScope::class)` é proibido fora de jobs super-admin auditados

### Camada 3 — PostgreSQL Row Level Security (RLS)
- RLS ativa nas 10 tabelas mais críticas: `calibrations`, `certificates`, `instruments`, `standards`, `service_orders`, `expenses`, `clients`, `tenants`, `users`, `audit_logs`
- Policy:
```sql
CREATE POLICY tenant_isolation ON calibrations
    USING (tenant_id = current_setting('app.current_tenant_id')::uuid);
```
- Conexão configura `app.current_tenant_id` via `SET LOCAL` no início de cada request
- Usuário da aplicação (`kalibrium_app`) **não tem** `BYPASSRLS` — apenas `kalibrium_superadmin` tem

### Tenant Context Middleware
```php
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id
            ?? throw new UnauthorizedException();

        TenantContext::set($tenantId);
        DB::statement("SET LOCAL app.current_tenant_id = ?", [$tenantId]);

        return $next($request);
    }
}
```

### Testes de Isolamento Obrigatórios no CI
- Teste de isolamento: usuário do Tenant A tenta acessar recurso do Tenant B → HTTP 403
- Teste de vazamento: query sem tenant scope lança `MissingTenantScopeException`
- Estes testes bloqueiam merge se falharem

## Consequências

### Positivas
- Defesa em profundidade: mesmo se o Global Scope for acidentalmente removido, o RLS bloqueia no nível do banco
- Custo de infraestrutura mantido baixo (single DB)
- Migrations unificadas — sem complexidade de schema-per-tenant
- PostgreSQL RLS é auditável e testável com `EXPLAIN`

### Negativas / Riscos
- Risco de `withoutGlobalScope` mal usado — mitigado com lint customizado (PHPStan rule)
- SET LOCAL no RLS exige connection pooling cuidadoso (PgBouncer em modo session, não transaction)
- Performance: índices compostos `(tenant_id, ...)` são obrigatórios em todas as tabelas

### Alternativas rejeitadas
- **Schema separado por tenant** — rejeitado: migrations paralelas, custo de manutenção alto no MVP com 50 tenants
- **Banco separado por tenant** — rejeitado: custo de infra inviável para MVP (50+ instâncias PG)
- **Apenas Global Scope sem RLS** — rejeitado: defesa única é insuficiente para dado metrológico S1

### Referências
- PostgreSQL 18 Row Security Policies: https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- OWASP Multi-tenancy: https://owasp.org/www-project-web-security-testing-guide/
