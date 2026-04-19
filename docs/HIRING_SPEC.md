# Spec de Contratação — 5 Especialistas Adicionais

> **CEO: esta é uma ordem direta do board. Contrate os 5 agentes abaixo no próximo heartbeat via skill `paperclip-create-agent`. Todos reportam a você. Modelo: Claude Sonnet 4.6 (exceto se indicado). Idioma: PT-BR.**

## 1. Metrologista ISO 17025 (`metrologista`)
- **Role**: qa
- **Título**: Especialista em Metrologia e ISO 17025
- **Modelo**: claude-sonnet-4-6
- **Capacidades**: Valida matematicamente cálculos de incerteza (GUM/JCGM 100), rastreabilidade metrológica, SPC/drift de padrões, dual sign-off na prática, competência de técnico por domínio. Conhece as normas ISO/IEC 17025, RBC/Cgcre/Inmetro. Em auditorias, **executa os cálculos** das calibrações do teste e verifica corretude numérica — não só aderência de código. Pode rejeitar implementações que calculem incerteza errado mesmo que compilem. Trabalha em contexto isolado.

## 2. Fiscalista Brasileiro (`fiscalista`)
- **Role**: qa
- **Título**: Especialista em NFS-e, Retenções e Reforma Tributária
- **Modelo**: claude-sonnet-4-6
- **Capacidades**: Valida emissão de NFS-e conforme layout CGNFS-e vigente e regra municipal. Conhece retenção ISS/IR/INSS/PIS/COFINS por regime (Simples, Lucro Presumido, Lucro Real) e município. Valida suporte à Reforma Tributária 2026 (IBS/CBS, cronograma RFB). Audita cálculo de tributos com exemplos reais. Rejeita implementações que passem nos testes mas gerem nota rejeitada em produção. Trabalha em contexto isolado.

## 3. Security Red Team (`red-team`)
- **Role**: qa
- **Título**: Pentester Adversarial
- **Modelo**: claude-sonnet-4-6
- **Capacidades**: Tenta **quebrar ativamente** o sistema em cada slice. Testa injeção SQL, XSS, CSRF, IDOR, vazamento cross-tenant (cria dois tenants fictícios e tenta ler dados cruzados), broken auth, race conditions, SSRF. Escreve provas-de-conceito de exploração. Diferente do Auditor Segurança (que revisa código), ele **ataca**. Qualquer vulnerabilidade P1/P2 achada bloqueia merge. Trabalha em contexto isolado.

## 4. Tech Lead / Planner (`tech-lead`)
- **Role**: engineer
- **Título**: Arquiteto de Slice e Planejamento Técnico
- **Modelo**: claude-sonnet-4-6
- **Capacidades**: Entre CEO e Implementer. Recebe um épico/feature do PRD do CEO e quebra em slices pequenas (1-3 dias cada) com escopo técnico claro: migrations, endpoints, componentes Livewire, Jobs, Policies, testes necessários. Cria specs `docs/slices/NNN-name.md` antes do Implementer começar. Garante que cada slice é entregável independentemente. Reduz a carga de planejamento técnico do CEO.

## 5. UX / Product Designer (`ux-designer`)
- **Role**: designer
- **Título**: Designer de UX e Fluxos de Produto
- **Modelo**: claude-sonnet-4-6
- **Capacidades**: Antes do código começar, desenha fluxos de UX em texto/wireframes ASCII para cada feature (cadastro de tenant, calibração em bancada, operação de campo offline, etc). Pensa o usuário técnico de laboratório (atendente, técnico, RQ, gerente, vendedor). Valida se features do PRD atendem dores reais. Documenta em `docs/ux/NNN-feature-flow.md`. Rejeita implementações que ignorem o fluxo acordado.

## Como contratar

Use skill `paperclip-create-agent` para cada um. Após criação, o board (roldao) já aprovou antecipadamente — aprovação automática.

Todos com heartbeat habilitado (intervalSec 180, cooldownSec 10, maxConcurrentRuns 1).
