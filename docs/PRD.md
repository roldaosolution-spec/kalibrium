# Kalibrium — Product Requirements Document (PRD)

> **Propósito:** Este documento é o briefing entregue a um CEO de IA autônomo que vai construir o Kalibrium do zero no repositório `github.com/roldaosolution-spec/kalibrium`.
>
> O CEO deve usar este PRD como fonte-de-verdade de produto. Decisões arquiteturais são vinculantes salvo proposta formal de mudança com ADR.

---

## 1. Visão de Produto

**Kalibrium é uma plataforma SaaS multi-tenant para laboratórios de calibração e empresas de serviço técnico em campo** que unifica toda a operação: desde o recebimento do equipamento até a emissão do certificado, passando pela operação de campo 100% offline. Resolve a fragmentação atual (planilha + software legado + portal fiscal + papel) em um sistema único que funciona na bancada, no campo com veículo operacional e com UMC (Unidade Móvel de Calibração).

### 8 dores críticas do mercado
1. Retrabalho administrativo — dados do cliente/instrumento/padrão inseridos em 3 lugares
2. Rastreabilidade metrológica frágil — trilha completa exige arqueologia em pastas
3. Tempo excessivo entre calibração e cobrança (7-14 dias vs. ideal <2 dias)
4. Invisibilidade para o cliente final — sem status em tempo real
5. Operação de campo invisível — técnico volta com caderno, digita horas depois
6. Sem rastreabilidade de custo real por OS — decisão de preço no instinto
7. CRM do vendedor é ausência (WhatsApp + planilha)
8. Conectividade intermitente destrói produtividade — 4 dias offline em campo

### Usuário-alvo
- Pequenos e médios laboratórios de calibração (até 2.000 calibrações/mês)
- Empresas de serviço técnico com frota operacional (até 10 técnicos, até 1 UMC)
- Domínios obrigatórios: Dimensional, Pressão, Massa (bancada, industrial média, industrial grande), Temperatura
- Acreditados RBC/Inmetro ou em processo
- Regime: Simples Nacional, Lucro Presumido

### Proposta única
- Offline-first com sync automática (funciona 4 dias sem internet)
- Multi-modo (bancada + campo-veículo + campo-UMC) no mesmo app
- Conformidade ISO 17025 / RBC built-in (dual sign-off, drift SPC, competência de técnico, suspensão retroativa)
- Fiscal integrado (NFS-e com retransmissão automática + Reforma Tributária 2026)
- Mobilidade nativa (PWA + Capacitor + push notifications)

---

## 2. Escopo do MVP

**Total: 80 requisitos** (29 originais + 33 de operação de campo + 10 de conformidade ISO 17025 e Reforma Tributária)

### Top 10 Features Prioritárias (ordenadas)

| # | Feature | Objetivo | Fluxo Principal | Critério de Sucesso |
|---|---------|----------|---|---|
| 1 | **Cadastro de Tenant + Usuários** | Isolamento total entre laboratórios | PM registra empresa, cadastra usuários com papel (gerente, técnico, motorista-UMC, vendedor, administrativo) | Dois tenants simultâneos sem vazamento de dado (teste: usuário do tenant A tenta ler certificado do tenant B → erro 403) |
| 2 | **Calibração em Bancada (fim-a-fim)** | Operação core — receber → calibrar → emitir → cobrar | Cliente entra → atendente cadastra instrumento → técnico mede com padrão → cálculo de incerteza → gerente aprova → certificado PDF → NFS-e → pagamento | Uma calibração completa em <2 dias corridos; certificado em PDF conforme RBC |
| 3 | **Operação de Campo Offline** | Técnico em local sem 4G calibra e sincroniza depois | Técnico abre app offline → registra calibração com foto/assinatura/despesa → app funciona 4 dias → sync quando voltar | App funciona 100% offline em Android; 8 OS pendentes no pior caso sem perder dado |
| 4 | **Fiscal (NFS-e + Reforma Tributária 2026)** | Emissão automática da nota conforme município, sem rejeição | Certificado aprovado → nota gerada automaticamente com retenção correta (ISS/IR/INSS) → enviada à prefeitura → XML ao cliente | Zero rejeição por cálculo; suporte a IBS/CBS da Reforma Tributária (jan/2026) |
| 5 | **Dual Sign-off de Certificado (ISO 17025)** | Conformidade RBC — duas pessoas assinam | Técnico executor → certificado em rascunho → verificador qualificado assina → emitido | Certificado não emite sem duas assinaturas distintas; bloqueio se executor = verificador |
| 6 | **Competência de Técnico (ISO 17025 §6.2)** | Técnico sem habilitação vigente não calibra | No início da calibração, app verifica data de validade da habilitação técnica por domínio → bloqueia se vencida (mesmo offline) | Técnico tenta calibrar dimensional sem habilitação vigente → app bloqueia antes de registrar leitura |
| 7 | **SPC + Drift Automático do Padrão** | Garantir rastreabilidade metrológica; alertar drift antes de crítico | Cada padrão tem gráfico de controle; sistema detecta tendência automática; dispara alerta antes do limite; bloqueia se crítico | Gráfico UCL/LCL atualizado a cada calibração do padrão; alerta 30 dias antes de vencer |
| 8 | **CRM do Vendedor Offline** | Vendedor em campo sem internet fecha orçamento | 500 clientes da carteira no celular (offline) → visita cliente → orçamento em PDF → envia via WhatsApp → converte em OS quando sincroniza | Orçamento gerado em <5 min; cliente recebe link direto; conversão em OS automática |
| 9 | **Caixa de Despesa por OS (com foto)** | Técnico registra custo real da viagem em tempo real | Despesa registrada com foto obrigatória + tipo + OS → saldo otimista atualiza em tempo real → triagem escritório → aprovação em alçada → reembolso em lote PIX | Custo real por OS rastreável; despesa aprovada em <24h; reembolso semanal |
| 10 | **Despacho Automático Round-Robin + Re-despacho** | Distribuição justa de OS entre técnicos; reagendar se técnico fica indisponível | OS criada → sistema atribui a técnico disponível do domínio por carga atual → se técnico ficar indisponível, re-dispatch automático em 48h | Distribuição balanceada; cliente notificado de mudança de técnico; gerente alerta de falta de técnico |

### Fora do escopo inicial
- App mobile nativo separado (PWA + Capacitor híbrido cobre ambos)
- Integração bancária automática (conciliação manual no MVP)
- Domínios não listados (elétricos, óticos, vazão, torque)
- Lucro Real como regime tributário
- Assinatura digital ICP-Brasil no certificado
- ERP externo (SAP, TOTVS)
- Múltiplas UMC com agenda consolidada cross-UMC

---

## 3. Stack Técnica

| Camada | Tecnologia | Justificativa |
|---|---|---|
| **Linguagem/Runtime** | PHP 8.4+ (JIT ativado) | Comunidade forte, hospedagem barata no Brasil |
| **Framework Web** | Laravel 13 | API + CI/CD nativo, Eloquent ORM, Horizon |
| **Frontend Web** | Livewire 4 + Alpine.js + Tailwind CSS 4 + Vite 8 | Reatividade sem JS separado, componentes em PHP |
| **Mobile / PWA** | PWA (service worker + offline) + Capacitor | Offline, push, biometria, câmera/GPS, iOS+Android com um código |
| **Banco de Dados** | PostgreSQL 18 | RLS nativo, JSONB, Full-Text Search PT-BR, pgcrypto, pg_trgm |
| **Multi-tenancy** | Single DB + `tenant_id` + Eloquent Global Scope + PostgreSQL RLS em 10 tabelas críticas | ADR-0016: defesa em profundidade |
| **Cache/Sessão** | Redis 8 + Predis | Session 4 dias offline, cache padrões embarcados, jobs |
| **Fila / Jobs** | Laravel Queues + Horizon | Jobs por domínio, supervisão em tempo real |
| **Audit Log** | owen-it/laravel-auditing + soft-delete | Append-only, retenção 10 anos RBC |
| **Fiscal** | sped-nfe + tcpdf (A1/A3) | NFS-e + XML, retransmissão automática |
| **Testes** | Pest 4 + Pest Browser Testing + Playwright | AC-ID rastreável, cobertura >80%, e2e |
| **Análise Estática** | PHPStan nível 8 + Pint + Rector | Rigor máximo |
| **CI/CD** | GitHub Actions | Build reproducível, SBOM CycloneDX |
| **Observabilidade** | Logs JSON → OpenTelemetry → Grafana; Prometheus | Tracing distribuído |
| **Storage** | Laravel Filesystem + S3 (MinIO self-hosted ou AWS `sa-east-1`) | LGPD residência Brasil |
| **Autenticação** | Laravel Fortify + Sanctum (JWT long-lived) | Built-in, 2FA TOTP, biometria via Capacitor |

### Hospedagem inicial
VPS Linux (Hostinger/KingHost) + Nginx + PHP-FPM + PostgreSQL managed (AWS RDS `sa-east-1`, RPO ≤15min, RTO ≤2h)

---

## 4. Requisitos Não-Funcionais

| Requisito | Target |
|---|---|
| Latência | p95 <500ms síncrono; p95 <2s geração PDF |
| Throughput | 50+ tenants MVP (até 2000 cal/mês cada) |
| Disponibilidade | 99.5% SLA inicial; RTO ≤2h |
| Offline-first | App mobile 100% offline por 4 dias |
| 2FA | TOTP obrigatório; biometria mobile |
| Criptografia | TLS 1.3 trânsito; AES-256 repouso |
| Multi-tenancy | Zero vazamento (S1 crítico); RLS+Scope+teste isolamento CI |
| LGPD | Portal titular; direitos ≤15 dias; breach 72h; base legal registrada |
| RBC/ISO 17025 | Dual sign-off, competência, SPC, drift, suspensão retroativa |
| Fiscal | NFS-e CGNFS-e vigente; Reforma Tributária 2026 IBS/CBS |
| Backup/DR | RPO ≤15min, RTO ≤2h; diário + snapshot mensal 12m |
| Retenção | Calibrações 10 anos append-only; logs acesso 5 anos |
| Acessibilidade | WCAG 2.1 AA; contraste ≥4.5:1 |
| Idioma | PT-BR no MVP (UI/email/PDF/NFS-e) |
| Responsivo | 375px smartphone / 768px tablet / 1280px desktop |
| Push | iOS+Android via Capacitor+FCM |

---

## 5. Decisões Arquiteturais-Chave

### ADRs recomendados (devem ser criados em `docs/adr/`)
- **ADR-0001:** Stack (Laravel 13 + PostgreSQL 18 + PWA + Capacitor) — **Aceita**
- **ADR-0004:** Identidade (Fortify + Sanctum + JWT + 2FA + biometria)
- **ADR-0015:** Offline-first mobile (PWA + Capacitor híbrido)
- **ADR-0016:** Isolamento multi-tenant (row-level `tenant_id` + Scope + RLS)
- **ADR-0017:** Testes com rastreabilidade AC-ID obrigatória

### Padrões arquiteturais
- **Hexagonal (Ports & Adapters):** domínio metrológico isolado de HTTP/DB
- **DDD:** agregados (Calibração, Padrão, Cliente, OS); bounded contexts (Metrologia / Fiscal / Operação de Campo)
- **Event Sourcing (parcial):** transições de estado da calibração; append-only
- **CQRS (light):** read queries separadas para dashboard/SPC

### Entidades-chave
- **Tenant** — isolamento raiz
- **Usuário** — RBAC (gerente, técnico, motorista-UMC, vendedor, administrativo)
- **Cliente** — empresa contratante
- **Instrumento** — paquímetro, balança, termômetro; SN, faixa, resolução
- **Padrão** — massa padrão, bloco padrão; SN, certificado vigente, validade
- **Procedimento** — roteiro técnico por domínio
- **Calibração** — pedido + instrumento + padrão + pontos + incerteza + certificado PDF
- **OS** — agrupa calibrações + despesa + modo + SLA
- **Despesa** — foto cupom + tipo + OS + aprovação
- **NFS-e** — emitida junto ao certificado aprovado
- **UMC / Veículo Operacional** — frota, diário de bordo, manutenção
- **Estoque Multinível** — 4 locais (lab central, UMC, veículo, carro pessoal)
- **Habilitação Técnica** — qualificação por domínio + data validade (ISO 17025 §6.2)
- **SPC** — histórico padrão com UCL/LCL
- **Suspensão Retroativa** — quando padrão falha, marca todos certificados afetados

---

## 6. Domínio do Negócio

### Glossário
- **Calibração** — comparação instrumento × padrão rastreado
- **Incerteza (GUM/JCGM 100)** — faixa de erro aceitável
- **Rastreabilidade Metrológica** — cadeia instrumento → procedimento → padrão → calibração padrão → SI
- **RBC** — Rede Brasileira de Calibração (Inmetro/Cgcre)
- **ISO 17025** — norma de competência técnica e metrológica
- **Padrão Primário/Secundário/Terciário** — hierarquia SI
- **Drift** — tendência do padrão se afastar do nominal (degradação)
- **UMC** — Unidade Móvel de Calibração
- **NFS-e** — Nota Fiscal de Serviço Eletrônica municipal
- **Retenção Fiscal** — desconto obrigatório (ISS/IR/INSS/PIS/COFINS)
- **Reforma Tributária 2026** — IBS/CBS substituem ISS/PIS/COFINS em alguns municípios

### Regras de negócio críticas
1. **Isolamento de Tenant (S1)** — vazamento cruzado é incidente crítico
2. **Dual Sign-off** — executor ≠ verificador; certificado não emite sem 2 assinaturas
3. **Competência de Técnico** — sem habilitação vigente, app bloqueia (mesmo offline)
4. **Validade de Padrão** — padrão vencido não pode ser usado (mesmo offline)
5. **Drift Automático** — SPC detecta tendência; alerta 30 dias antes de crítico
6. **Suspensão Retroativa** — padrão falha → todos certificados afetados são identificados e RQ decide
7. **Revalidação Proativa** — 90 dias antes do certificado vencer, dispara email+WhatsApp
8. **Despacho Round-Robin** — OS atribuída por domínio e carga atual
9. **Re-despacho Automático** — técnico indisponível → reatribui próximas 48h
10. **Fiscal Automática** — NFS-e sai junto ao certificado aprovado

### Conformidade regulatória
- **LGPD** — portal titular, direitos ≤15d, breach 72h, residência BR
- **RBC/ISO 17025** — Cgcre auditoria conforme
- **INMETRO** — procedimentos registrados por domínio
- **Fiscal CGNFS-e + Reforma 2026 (jan/2026)** — IBS/CBS
- **Residência de Dados** — `sa-east-1` ou MinIO self-hosted BR

---

## 7. Roadmap (DEFINIR)

CEO deve decidir:
- Timeline de cada feature do MVP
- Data-alvo de go-live
- Pós-MVP (Y2): Lucro Real, app nativo, integrações bancárias, domínios adicionais
- Plano de crescimento de tenants (10 → 50 → 500)

---

## 8. Restrições / Constraints

### Tecnologia DEVE
- PHP 8.4+ / Laravel 13 / PostgreSQL 18 / PWA + Capacitor
- Fortify + Sanctum + 2FA + biometria
- Row-level + Global Scope + PostgreSQL RLS

### Tecnologia NÃO PODE
- Next.js, Python, Go, MySQL, Oracle
- OAuth sem 2FA
- App nativo iOS/Android separados no MVP

### Conformidade DEVE
- RBC: dual sign-off, competência, SPC, drift, suspensão retroativa
- Fiscal: NFS-e + Reforma Tributária 2026
- LGPD: portal, rastreabilidade, residência BR, breach 72h

### Operacional DEVE
- Offline 4 dias
- Zero vazamento tenant
- Append-only + retenção 10 anos
- p95 <500ms / PDF <2s

### Decisões pendentes (CEO decide)
- Orçamento / timeline / headcount
- Data-alvo MVP
- DPO designado (LGPD)
- Contador responsável (Reforma Tributária)

---

## Riscos Críticos da Reconstrução

1. **Multi-tenant errado** — custa caro; ADR-0016 desde sprint 1
2. **Fiscal Reforma 2026** — começa jan/2026; implementar ou adiar pós-MVP (decisão do CEO)
3. **LGPD sem DPO** — designar antes do primeiro onboarding
4. **Offline-first complexo** — começar simples (last-write-wins), evoluir se necessário

---

**Este PRD é a fonte-de-verdade de produto. CEO tem autoridade para quebrar em épicos/slices, priorizar, e executar via time de agentes (implementer + 10 auditores + fixer + PR-approver).**
