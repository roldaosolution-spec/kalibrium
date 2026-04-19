# Slice 003 — Auth + RBAC

## Deliverables

| ID | Entregável | Status |
|----|-----------|--------|
| AC-003-01 | Registro de usuário via Livewire (Fortify) | ✅ |
| AC-003-02 | Login web + logout via Livewire | ✅ |
| AC-003-03 | Tokens Sanctum para API mobile (`POST /api/tokens/create`) | ✅ |
| AC-003-04 | RBAC: roles `gerente`, `tecnico`, `motorista_umc`, `vendedor`, `administrativo` + middleware `EnsureRole` | ✅ |
| AC-003-05 | Políticas de autorização (`UserPolicy`) + isolamento de tenant | ✅ |
| AC-003-06 | Fluxo 2FA TOTP (ativação, QR code, códigos de recuperação) | ✅ |

## Resumo de Testes

- **39 testes / 75 assertions** — todos verdes no merge
- Cobertura: registro, login, tokens API, middleware de role, UserPolicy, 2FA setup

## Correções de Auditoria (Slice 003 Audit Fixes — [KAL-46](/KAL/issues/KAL-46))

| Finding | Severidade | Fix Aplicado |
|---------|-----------|-------------|
| F-001 | CRÍTICO | `role` removido do formulário; padrão `Role::Tecnico` atribuído server-side |
| F-002 | CRÍTICO | `tenant_id` removido como campo controlado pelo usuário; derivado de parâmetro de URL com `#[Locked]` |
| F-003 | ALTO | `throttle:5,1` adicionado à rota `POST /api/tokens/create` |
| F-004 | ALTO | `RateLimiter::attempt()` adicionado ao componente Livewire `Login` |
| F-005 | MÉDIO | `Register.php` usa trait `PasswordValidationRules` (consistência com Fortify) |
| F-006 | MÉDIO | `Features::emailVerification()` habilitado; `User` implementa `MustVerifyEmail` |
| F-007 | BAIXO | `Prunable` adicionado ao `User`; soft-deletes > 30 dias purgados diariamente |

## Limitações Conhecidas

- **2FA não obrigatório** — fluxo de ativação existe mas não é enforçado para todos os roles; rastreado como [KAL-45](/KAL/issues/KAL-45)
- **Fluxo de convite de tenant** — `tenant_id` derivado de query param assinado ainda não implementado; registro requer link gerado por gerente com UUID de tenant válido
- **Biometria mobile** — prevista no ADR-0004 mas fora do escopo do MVP; planejada para slice futuro

## ADR Relacionado

[ADR-0004 — Identidade: Fortify + Sanctum + JWT + 2FA + Biometria](../adr/ADR-0004-identity.md)
