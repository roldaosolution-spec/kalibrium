# AGENTS.md — Kalibrium

## Idioma (OBRIGATÓRIO — aplica a TODOS os agentes)

- **Toda comunicação com o board (humano) deve ser em Português Brasileiro** (PT-BR).
- **Comentários em issues, PRs, tarefas, descrições e mensagens internas entre agentes: Português Brasileiro.**
- **Mensagens de commit, nomes de branch: inglês** (padrão da indústria).
- **Código-fonte (nomes de variáveis, classes, métodos): inglês.**
- **Documentação técnica (docstrings, README técnico, comentários "why"): Português Brasileiro.**
- **ADRs, specs de produto, PRD, RFCs: Português Brasileiro.**

Se em dúvida: **PT-BR é o default**. Inglês só para código e commits.

## Fonte-de-verdade
1. **PRD** — `docs/PRD.md` (escopo, features, stack, constraints)
2. **ADRs** — `docs/adr/*.md` (decisões arquiteturais)

## Regras de processo
- Todo código passa por **audit panel de 10 especialistas** antes de PR.
- **Qualquer finding** bloqueia merge → Fixer aplica correções → re-audit até zero findings.
- PR é revisado por agente **PR-Approver** que decide merge em `main`.
- **Sem merge direto em `main`** por implementer ou auditor.

## Padrão de commit
`type(scope): mensagem em inglês` — ex: `feat(tenant): add multi-tenant isolation with RLS`

## Branches
- `main` — produção (só PR-Approver merga)
- `feat/slice-XXX-kebab-description` — trabalho dos agentes por slice

## Ao começar qualquer tarefa
1. Ler `docs/PRD.md` (se ainda não leu)
2. Ler ADRs aceitos em `docs/adr/*.md`
3. Verificar a slice ativa (issue assignada a você)
4. Se for dúvida de produto, perguntar ao CEO via comentário em PT-BR
