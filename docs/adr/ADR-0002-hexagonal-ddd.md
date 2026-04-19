# ADR-0002 — Arquitetura Hexagonal + DDD

| Campo         | Valor                                              |
|---------------|----------------------------------------------------|
| **Status**    | Aceita                                             |
| **Data**      | 2026-04-19                                         |
| **Autores**   | Arquiteto (IA), CEO (IA)                           |
| **Revisores** | Painel de Auditores (10 especialistas)             |

---

## Contexto

O Kalibrium é uma plataforma SaaS multi-tenant para laboratórios de calibração regulados por ISO 17025 / RBC / LGPD. O domínio é rico em regras de negócio (rastreabilidade de padrões, dupla assinatura, isolamento de tenant). Uma arquitetura puramente MVC tenderia a concentrar lógica nos models Eloquent, dificultando testes unitários, auditoria e evolução independente dos slices.

O auditor KAL-10 identificou a ausência de convenções explícitas de Arquitetura Hexagonal e DDD como bloqueador para o Slice 002, uma vez que as equipes precisam de contrato claro sobre onde cada tipo de artefato vive.

---

## Decisão

Adotamos **Arquitetura Hexagonal (Ports & Adapters)** com camadas DDD para toda lógica de domínio não trivial, coexistindo com Eloquent para persistência.

### Estrutura de Diretórios

```
app/
├── Domain/                        # Núcleo de domínio — sem dependências de framework
│   ├── {BoundedContext}/
│   │   ├── Aggregates/            # Raízes de agregado (sem Eloquent)
│   │   ├── Entities/              # Entidades do domínio
│   │   ├── ValueObjects/          # Objetos de valor imutáveis
│   │   ├── Events/                # Eventos de domínio
│   │   ├── Exceptions/            # Exceções de domínio
│   │   ├── Ports/
│   │   │   ├── Inbound/           # Interfaces de casos de uso (use-case ports)
│   │   │   └── Outbound/          # Interfaces de repositório/serviço externo
│   │   └── Services/              # Serviços de domínio stateless
│
├── Application/                   # Orquestradores — coordenam domínio, sem regra de negócio
│   ├── {BoundedContext}/
│   │   ├── Commands/              # DTOs de comando (CQRS write side)
│   │   ├── Queries/               # DTOs de query (CQRS read side)
│   │   └── Handlers/              # Command Handlers e Query Handlers
│
├── Infrastructure/                # Adaptadores outbound — implementam Ports/Outbound
│   ├── Persistence/
│   │   └── Eloquent/
│   │       ├── Models/            # Eloquent models (mapeamento ORM)
│   │       └── Repositories/      # Implementações de repositório com Eloquent
│   ├── Cache/                     # Adaptadores de cache
│   ├── Queue/                     # Jobs, Events broadcast
│   └── ExternalServices/          # Clientes HTTP (NFS-e, WhatsApp, FCM)
│
├── Http/                          # Adaptadores inbound — Controllers, Middleware, Requests
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
└── Models/                        # Mantido para compatibilidade; novos models em Infrastructure/
```

### Contextos Delimitados (Bounded Contexts)

| Contexto             | Responsabilidade                                                      |
|----------------------|-----------------------------------------------------------------------|
| `Identity`           | Usuários, autenticação, 2FA (Fortify + Sanctum)                       |
| `Tenant`             | Laboratórios, planos, isolamento multi-tenant                         |
| `Calibration`        | Ordens de serviço, instrumentos, padrões, dupla assinatura            |
| `Compliance`         | Trilha de auditoria, documentação regulatória (ISO 17025)             |
| `Billing`            | NFS-e, planos de assinatura                                           |
| `Notification`       | WhatsApp, FCM, e-mail transacional                                    |

### Convenções de Agregados

- **Raiz de Agregado** identifica o limite de consistência transacional. Toda mutação passa pela raiz.
- Agregados se comunicam via **eventos de domínio**, nunca por referência direta.
- IDs de agregados são **UUIDs** (`Str::uuid()`) gerados na camada de domínio, nunca auto-incremento.
- Agregados não dependem de `Illuminate\*`; são PHPPuro com interfaces.

### Coexistência Eloquent + Repository

- Eloquent `Model` em `Infrastructure/Persistence/Eloquent/Models/` é responsável **apenas por mapeamento ORM**.
- A interface de repositório (`Ports/Outbound/`) recebe e retorna **entidades de domínio**, nunca models Eloquent.
- A implementação `Infrastructure/Persistence/Eloquent/Repositories/` converte entre Eloquent model e entidade de domínio.
- Em Slices iniciais onde o domínio ainda é simples, é **permitido** usar Eloquent diretamente no Controller + Service, desde que o code path seja coberto por testes de feature e o débito técnico seja registrado como issue.

### Regras de Dependência

```
Http → Application → Domain  (direção permitida)
Infrastructure → Domain       (implementa ports)
Domain → nada externo         (sem imports de framework)
```

Dependência inversa é violação de arquitetura e será bloqueada via PHPStan + Rector nas próximas sprints.

---

## Consequências

**Positivas:**
- Domínio testável sem banco de dados ou framework.
- Regras de negócio auditáveis isoladamente — facilita ISO 17025 §7.5 e dupla assinatura.
- Facilita substituição de adaptadores (ex.: trocar Eloquent por DBAL em queries analíticas).

**Negativas / Riscos:**
- Maior cerimônia para features simples — mitigado pela permissão de uso direto de Eloquent em domínios simples.
- Curva de aprendizado para novos membros — mitigada por este ADR e exemplos no Slice 002.

---

## Alternativas Consideradas

| Alternativa              | Motivo para rejeição                                              |
|--------------------------|-------------------------------------------------------------------|
| MVC puro (Laravel padrão) | Acoplamento alto; impossível testar regras de calibração sem banco |
| CQRS completo com Event Sourcing | Prematura para o estágio atual; considerar no Slice 005+   |

---

## Referências

- [ADR-0001](/docs/adr/ADR-0001-stack.md) — Stack tecnológica
- [ADR-0016](/docs/adr/ADR-0016-multitenant.md) — Estratégia multi-tenant
- [ADR-0017](/docs/adr/ADR-0017-tests.md) — Testes com rastreabilidade AC-ID
- Vaughn Vernon — *Implementing Domain-Driven Design*
- Alistair Cockburn — *Hexagonal Architecture*
