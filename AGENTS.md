# AGENTS.md — Kalibrium

## Idioma (OBRIGATÓRIO — aplica a TODOS os agentes)

- Toda comunicação com o board e entre agentes: **Português Brasileiro** (PT-BR).
- Issues, PRs, comentários, descrições de tarefas: **PT-BR**.
- Docs (PRD, ADRs, specs, docstrings): **PT-BR**.
- Código-fonte (nomes de variáveis, classes, métodos): **inglês**.
- Commit messages e nomes de branch: **inglês** (Conventional Commits).

## Fonte-de-verdade
1. **PRD** — `docs/PRD.md` (escopo, features, stack, constraints)
2. **ADRs** — `docs/adr/*.md` (decisões arquiteturais)
3. **Hiring Spec** — `docs/HIRING_SPEC.md` (agentes a contratar)
4. **Slices** — `docs/slices/*.md` (especificações técnicas por slice)
5. **UX Flows** — `docs/ux/*.md` (fluxos desenhados pelo UX Designer)

## Time (19 agentes-alvo)
- **1 CEO** (Opus 4.6) — estratégia, delegação, decisões de produto
- **1 Tech Lead / Planner** — quebra épicos em slices técnicas
- **1 UX Designer** — fluxos de usuário antes do dev
- **1 Implementer** — escreve o código
- **1 Fixer** — aplica correções após findings
- **1 PR Approver** — revisa e **merga automaticamente** em `main`
- **13 Auditores especialistas** (contexto isolado entre si):
  - Arquiteto, Segurança, Performance, Testes, Quality
  - Laravel, Frontend-PWA, Docs, Compliance, DevOps
  - **Metrologista ISO 17025**, **Fiscalista BR**, **Red Team**

## Regras de processo

1. Tech Lead recebe feature do PRD → cria `docs/slices/NNN-name.md`
2. UX Designer desenha fluxo → `docs/ux/NNN-flow.md` (em paralelo)
3. Implementer cria branch `feat/slice-NNN-kebab`, codifica com testes
4. Implementer abre PR → auditores (13) rodam em paralelo em contexto isolado
5. Qualquer finding crítico → Fixer aplica correções → re-audit (loop infinito até zero findings)
6. PR-Approver revisa PR e CI → **mergeia automaticamente** em `main` com squash
7. PR-Approver comenta em PT-BR no PR e notifica CEO

## Auto-merge (NOVO)
- PR-Approver **NÃO** espera aprovação humana.
- Se auditoria passou + CI verde + PR atualizado com main → `gh pr merge --squash --delete-branch`.
- Humano só é chamado se houver ambiguidade de produto (não de código).

## Padrão de commit
`type(scope): mensagem em inglês` — ex: `feat(tenant): add multi-tenant isolation with RLS`

Tipos: feat, fix, chore, docs, test, refactor, perf, ci, build

## Branches
- `main` — produção (só PR-Approver merga automaticamente)
- `feat/slice-NNN-descricao` — trabalho por slice

## Ao começar qualquer tarefa
1. Ler `docs/PRD.md` (se ainda não leu)
2. Ler ADRs aceitos em `docs/adr/*.md`
3. Verificar a issue/slice ativa atribuída a você
4. Se dúvida de produto: comentar em PT-BR, marcar `@ceo`

## Token GitHub
`gh` está autenticado com PAT em `$HOME/.git-credentials`. Use `gh auth status` pra validar.
