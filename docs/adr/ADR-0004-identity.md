# ADR-0004 — Identidade: Fortify + Sanctum + JWT + 2FA + Biometria

| Campo       | Valor                                              |
|-------------|----------------------------------------------------|
| **Status**  | Aceita                                             |
| **Data**    | 2026-04-19                                         |
| **Autores** | CEO (IA), Equipe de Segurança                      |
| **Revisores** | Painel de Auditores                              |

---

## Contexto

O Kalibrium lida com dados metrológicos sensíveis e precisa cumprir:
- **ISO 17025 §6.2** — Competência de técnico verificada a cada acesso
- **LGPD** — Breach notification em 72h, base legal registrada, portal do titular
- **RBC** — Auditoria de acesso para todos os certificados
- Operação mobile offline com autenticação válida por até 4 dias sem internet
- Multi-tenancy com isolamento total — usuário do Tenant A não pode acessar nada do Tenant B

## Decisão

Adotar a seguinte arquitetura de identidade:

### 1. Autenticação Web (Fortify)
- `laravel/fortify` gerencia login, registro, reset de senha e confirmação de e-mail
- Sessões via Redis com TTL de 4 dias (alinhado ao offline-first)
- Login bloqueado após 5 tentativas com backoff exponencial (Fortify `RateLimiter`)

### 2. Autenticação Mobile / API (Sanctum + JWT long-lived)
- `laravel/sanctum` emite tokens de acesso com TTL de 4 dias para suportar operação offline
- Token renovado automaticamente no sync pós-field
- Revogação centralizada por `personal_access_tokens.expires_at`

### 3. Autenticação de Dois Fatores (2FA obrigatório)
- TOTP (RFC 6238) via `laravel/fortify` com `two-factor-authentication`
- QR Code na ativação; 8 códigos de recuperação single-use armazenados em `bcrypt`
- 2FA obrigatório para todos os usuários (gerente, técnico, motorista-UMC, vendedor, administrativo)
- Sessão marcada `two_factor_confirmed_at` — endpoints sensíveis exigem confirmação recente (max 1h)

### 4. Biometria Mobile (Capacitor)
- Capacitor Biometrics Plugin usa Face ID / Touch ID / sensor Android para desbloquear o token armazenado no Keychain/Keystore nativo
- O token JWT não sai do dispositivo; biometria apenas autoriza o acesso local ao token
- Fallback para PIN numérico se biometria indisponível

### 5. RBAC (Roles & Permissions)
- Roles: `gerente`, `tecnico`, `motorista_umc`, `vendedor`, `administrativo`
- Permissões granulares por papel + tenant
- Técnico bloqueado se habilitação vencida (ISO 17025 §6.2) — verificado offline com cache local

### 6. Isolamento de Tenant na Camada de Identidade
- Campo `tenant_id` no `users` — preenchido obrigatoriamente no registro
- Middleware `EnsureTenantScope` resolve o tenant do usuário autenticado e injeta Global Scope
- Nenhuma rota autenticada retorna dados de outro tenant

## Consequências

### Positivas
- Fortify + Sanctum são nativos do ecossistema Laravel — sem dependências externas complexas
- JWT long-lived resolve o problema de offline-4-dias sem precisar de PKCE ou authorization server externo
- Biometria via Capacitor Keychain/Keystore: token nunca trafega em texto claro no dispositivo

### Negativas / Riscos
- Token de 4 dias tem janela longa — mitigado por revogação via `sanctum:purge` no sync e no logout
- TOTP sem hardware token — aceitável para MVP; revisar se RBC exigir hardware token

### Alternativas rejeitadas
- **OAuth 2.0 (Passport)** — rejeitado: OAuth sem 2FA é constraint do PRD; Passport adiciona complexidade desnecessária no MVP
- **Auth0 / Cognito** — rejeitado: residência de dados BR obrigatória (LGPD + RBC)
- **Biometria sem Keychain** — rejeitado: armazenar token em localStorage é vulnerabilidade crítica

## Dívidas Técnicas Conhecidas

| ID | Descrição | Rastreado em |
|----|-----------|-------------|
| D-001 | 2FA obrigatório para Gerente/Admin ainda não enforçado — usuários podem pular a configuração de 2FA | [KAL-45](/KAL/issues/KAL-45) |
| D-002 | Biometria mobile (Capacitor) não implementada no MVP — prevista para Slice futuro | backlog |
