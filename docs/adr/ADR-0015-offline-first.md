# ADR-0015 — Offline-First Mobile: PWA + Capacitor Híbrido

| Campo       | Valor                                              |
|-------------|----------------------------------------------------|
| **Status**  | Proposta                                           |
| **Data**    | 2026-04-19                                         |
| **Autores** | CEO (IA), Equipe Mobile                            |
| **Revisores** | Painel de Auditores                              |

---

## Contexto

Um dos requisitos críticos do Kalibrium é que técnicos de campo consigam operar **100% offline por até 4 dias consecutivos** — incluindo:
- Registro de calibrações com pontos de medição, incerteza e fotos
- Verificação de habilitação técnica vigente (ISO 17025 §6.2) sem internet
- Verificação de validade de padrões de medição sem internet
- Registro de despesas com foto do cupom
- Geração de rascunho de OS e orçamento

Além disso, o MVP exige iOS + Android **com um único code-base** (sem app nativo separado — constraint do PRD).

## Decisão

Adotar a arquitetura **PWA + Capacitor híbrido**:

### 1. Camada Web (Laravel Blade + Livewire + Vite)
- O frontend web usa Livewire 4 para reatividade server-side
- Em produção desktop/bancada: SSR completo via Laravel

### 2. PWA (Progressive Web App)
- Service Worker (Workbox) com estratégia:
  - **Cache-first** para assets estáticos (CSS/JS/imagens de produto)
  - **Network-first com fallback** para leituras de dados
  - **Background sync** para escritas offline (calibrações, despesas, fotos)
- Manifesto `manifest.webmanifest` com `display: standalone`, ícones e splash screens
- Instalação no dispositivo via "Adicionar à tela inicial"

### 3. Capacitor (iOS + Android nativo)
- Capacitor 6+ empacota o PWA como app nativo
- Plugins nativos obrigatórios:
  - `@capacitor/camera` — foto do cupom de despesa e instrumento
  - `@capacitor/filesystem` — armazenamento de dados offline
  - `@capacitor/push-notifications` — FCM para alertas de OS, vencimento de padrão
  - `@capacitor-community/biometric-auth` — Face ID / Touch ID / biometria Android
  - `@capacitor/geolocation` — localização do técnico na OS
  - `@capacitor/network` — detecção de conectividade para disparar sync
- Build: Xcode (iOS) e Android Studio (Android) no CI (GitHub Actions)

### 4. Estratégia de Dados Offline (Last-Write-Wins — MVP)
- **Entidades embarcadas no dispositivo na abertura do turno:**
  - Clientes da carteira do vendedor (até 500)
  - Instrumentos com OS pendentes (até 8 por turno)
  - Padrões com validade e coeficientes de incerteza
  - Habilitações técnicas do usuário logado
  - Procedimentos técnicos por domínio
- **Armazenamento local:** SQLite via `@capacitor-community/sqlite` ou IndexedDB com Dexie.js
- **Sync:** ao detectar conectividade via `@capacitor/network`, background sync envia delta compactado (JSON Patch)
- **Conflito (MVP):** Last-Write-Wins com timestamp do servidor; conflitos registrados em log para revisão manual
- **Limite de 4 dias:** token Sanctum com TTL de 4 dias; ao expirar, requer autenticação online

### 5. Cache de Padrões e Habilitações (Segurança da Medição)
- No início de cada turno (online), app baixa e assina criptograficamente:
  - Lista de padrões válidos com data de validade
  - Lista de habilitações técnicas vigentes
- Offline, app valida contra a lista assinada — não permite medição com padrão vencido ou técnico sem habilitação

## Consequências

### Positivas
- Um único code-base (Laravel/Livewire + Capacitor) para web + iOS + Android
- PWA funciona mesmo sem instalação nas lojas (URL direta)
- Capacitor acessa hardware nativo (câmera, biometria, GPS, push) sem React Native
- Sem duplicação de lógica de negócio: domínio metrológico vive 100% no backend Laravel

### Negativas / Riscos
- Performance UI no mobile pode ser inferior a app nativo puro — aceitável para o MVP
- Background sync requer tratamento cuidadoso de conflitos — Last-Write-Wins é simplificação; revisar no Y1 se problemas surgirem
- Rejeição nas lojas por apps PWA-wrapped é risco real no iOS — documentar justificativa e testar review

### Alternativas rejeitadas
- **React Native** — rejeitado: dois code-bases (web + mobile); sem aproveitamento do domínio Laravel
- **App nativo iOS/Android** — rejeitado (constraint explícita do PRD)
- **Só PWA sem Capacitor** — rejeitado: sem acesso a biometria nativa, push iOS, câmera com qualidade
- **Electron** — fora de escopo (desktop apenas)
