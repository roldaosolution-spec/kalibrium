# AGENTS.md — Kalibrium

## Fonte-de-verdade
1. **PRD** — `docs/PRD.md` (escopo, features, stack, constraints)
2. **ADRs** — `docs/adr/*.md` (decisões arquiteturais)

## Regras
- Todo código passa por **audit panel de 10 especialistas** antes de PR
- **Qualquer finding** bloqueia merge → Fixer aplica correções → re-audit até zero findings
- PR é revisado por agente **PR-Approver** que decide merge em `main`
- **Sem merge direto em main** por implementer ou auditor

## Padrão de commit
`type(scope): mensagem` — ex: `feat(tenant): cadastro multi-tenant com RLS`

## Branches
- `main` — produção (só PR-Approver merga)
- `feat/slice-XXX-descricao` — trabalho dos agentes por slice

## Ao começar qualquer tarefa
1. Ler `docs/PRD.md` (se ainda não leu)
2. Ler `docs/adr/*.md` aceitos
3. Verificar slice atual em `docs/SLICES.md`
