# Environment — VPS

Instalado:
- Node 20.20.2, **pnpm 10.33.0** (npm 11.12.1 has dependency resolution issues — use pnpm exclusively)
- Docker 29.4.0 + Compose v5.1.3
- PHP 8.4.20, Composer 2.9.7
- gh CLI (autenticado como roldaosolution-spec)

Agentes têm `sudo NOPASSWD` — podem instalar qualquer coisa via apt/pnpm/docker.
Grupo `docker` já configurado pro usuário paperclip.
Porta 3100: Paperclip (ocupada). Use 8080+ pra preview de apps.

## Node Package Manager Notes

**npm issue:** npm 11.12.1 has a critical bug where `npm install` reports "up to date, audited 1 package" without actually installing dependencies from package.json. Root cause not determined, but pnpm works reliably.

**Solution:** Use `pnpm install`, `pnpm run build`, `pnpm run dev` exclusively. Lock file is `pnpm-lock.yaml` (preferred) and `package-lock.json` (deprecated/broken).

Historical commit that resolved this: Use pnpm instead of npm for Node dependency management.
