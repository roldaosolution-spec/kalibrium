# ADR-0001 — Stack Técnica: Laravel 13 + PostgreSQL 18 + PWA + Capacitor

| Campo       | Valor                                              |
|-------------|----------------------------------------------------|
| **Status**  | Aceita                                             |
| **Data**    | 2026-04-19                                         |
| **Autores** | CEO (IA), Equipe de Arquitetura                    |
| **Revisores** | Painel de Auditores                              |

---

## Contexto

O Kalibrium precisa suportar:
- Operação de bancada (web SPA ou server-side render)
- Operação de campo 100% offline por até 4 dias (mobile)
- Conformidade ISO 17025 / RBC com rastreabilidade metrológica
- Multi-tenancy com isolamento total entre laboratórios
- Stack barata de hospedar no Brasil (AWS `sa-east-1` ou VPS Hostinger/KingHost)
- Ciclo de desenvolvimento rápido com equipe pequena

## Decisão

Adotar a seguinte stack:

| Camada | Tecnologia | Versão mínima |
|---|---|---|
| Linguagem | PHP (JIT ativo) | 8.4+ |
| Framework Web | Laravel | 13.x |
| Frontend | Livewire + Alpine.js + Tailwind CSS + Vite | 4 / 3 / 4 / 8 |
| Mobile / PWA | Service Worker (offline) + Capacitor (iOS/Android) | — |
| Banco Primário | PostgreSQL | 18 |
| Cache / Fila / Sessão | Redis | 8 |
| Autenticação | Laravel Fortify + Sanctum (JWT long-lived) | — |
| Testes | Pest | 4 |
| Análise Estática | PHPStan nível 8 + Pint + Rector | — |
| CI/CD | GitHub Actions | — |
| Observabilidade | Logs JSON → OpenTelemetry → Grafana | — |

## Consequências

### Positivas
- PHP 8.4 + Laravel 13 = ecossistema maduro, hospedagem acessível no Brasil
- PostgreSQL 18 oferece RLS nativo, JSONB, FTS PT-BR, pgcrypto — essenciais para multi-tenancy seguro e busca metrológica
- PWA + Capacitor = um único código-base para web, iOS e Android; offline-first nativo
- Livewire reduz a superfície de JavaScript e acelera o desenvolvimento de formulários complexos (calibração, NFS-e)
- PHPStan level 8 + Pest 4 garantem rigor máximo para domínio metrológico crítico

### Negativas / Riscos
- PHP não é a primeira escolha para microsserviços de alta escala, mas o volume do MVP (50 tenants, 2.000 cal/mês cada) cabe confortavelmente
- Capacitor requer build nativo (Xcode/Android Studio) para submissão nas lojas — CI deve ter suporte
- PostgreSQL 18 ainda recente (lançamento ~2025); ficar atento a bugs em produção

### Alternativas rejeitadas
- **Next.js + Node** — rejeitado (constraint do PRD: sem Next.js)
- **MySQL** — rejeitado (sem RLS nativo; constraint do PRD)
- **App nativo Swift/Kotlin** — rejeitado (dois code-bases; constraint do PRD)
- **Python/Django** — rejeitado (constraint do PRD)
