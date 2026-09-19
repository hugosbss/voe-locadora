# Locadora Veicular — Segurança & Operação

Manual técnico do endurecimento de segurança aplicado ao sistema de cadastro de
clientes e do funcionamento operacional (migração, backup, retenção). Complementa
o README com os detalhes de implementação e configuração.

---

## 1. Ambiente de produção

### Variáveis críticas (.env)

| Variável | Descrição | Padrão |
| --- | --- | --- |
| `APP_KEY` | Chave de criptografia (AES-256-CBC) — nunca vazar | gerada |
| `APP_URL` | URL pública; **usar HTTPS** | — |
| `APP_FORCE_HTTPS` | Habilita redirect HTTP→HTTPS e HSTS | `false` |
| `APP_TRUSTED_PROXIES` | IPs dos proxies (LB/CDN) para respeitar `X-Forwarded-*` | — |
| `ADMIN_PASSWORD` | Somente para `admin:provision`/seeder | obrigatória (≥12) |
| `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` | Credenciais do MySQL (o backup lê o `.env` da aplicação) | — |
| `BACKUP_ENABLED` | Habilita `backup:cadastros` no cron | `false` |
| `BACKUP_PATH` | Diretório de destino do backup criptografado (`config/backup.php`) | `storage/app/backups` |
| `BACKUP_RETENTION_DAYS` | Retenção de cópias em `config/backup.php` | `30` |
| `RATE_LIMIT_CADASTRO_MAX_ATTEMPTS` | Submissões totais do cadastro por IP / janela (anti-flood) | `60` / `15min` |
| `RATE_LIMIT_CADASTRO_SUCCESS_MAX_ATTEMPTS` | Cadastros efetivamente criados por IP / janela (anti-spam) | `10` / `15min` |
| `RATE_LIMIT_CADASTRO_DECAY_MINUTES` / `RATE_LIMIT_CADASTRO_SUCCESS_DECAY_MINUTES` | Janelas dos contadores de cadastro | `15` |
| `RATE_LIMIT_CEP_PER_MINUTE` | Consultas de CEP por IP/minuto | `30` |
| `RATE_LIMIT_LOGIN_PER_MINUTE` | Tentativas de login por IP+email/minuto | `5` |
| `RATE_LIMIT_PASSWORD_RESET` | Solicitações de redefinição de senha por IP / janela | `6` / `10min` |
| `RATE_LIMIT_PASSWORD_RESET_DECAY_MINUTES` | Janela do limite de redefinição de senha | `10` |
| `LOGIN_LOCK_THRESHOLD` | Falhas para início do bloqueio progressivo | `10` |
| `LOGIN_LOCK_MINUTES` | Duração inicial do bloqueio | `30` |
| `RETENTION_*` | Prazos de retenção por status (dias) | ver `config/retention.php` |
| `PRIVACY_POLICY_VERSION` | Versão da Política de Privacidade registrada em cada consentimento | `1.0` |

### Web server / proxies

- `config/session.php`: cookies `HttpOnly`, `Secure` (via `APP_FORCE_HTTPS`/proxy),
  `SameSite=Lax`, lifetime curto de sessão.
- Middlewares registrados em `bootstrap/app.php`: `SecurityHeaders`, `ForceHttps`
  (quando habilitado), `StartSession`, rate limiters.
- CSRF habilitado em todas as rotas web; sessão é regenerada no login/logout.
- Em produção com HTTPS, defina `APP_FORCE_HTTPS=true` e `APP_TRUSTED_PROXIES`
  (incluir o IP do reverse proxy para que o rate limit enxergue o IP real do cliente).

## 2. Authentication administrativa

- Login em **duas etapas** quando o 2FA está habilitado:
  1. `POST /admin/login` valida e-mail + senha (mensagem genérica — não revela
     se a conta existe) e registra auditoria `LoginFailed`/`LoginSuccess`.
  2. `POST /admin/login/2fa` exige TOTP (RFC 6238) ou código de recuperação.
- `LoginThrottleService` bloqueia por **IP|email** após `LOGIN_LOCK_THRESHOLD`
  falhas; o rate limiter `admin_login` (5/min) protege a rota.
- 2FA habilitável/desabilitável em `/admin/seguranca`, exigindo senha (e TOTP
  corrente na desativação). Segredo criptografado (`User::$casts['encrypted']`);
  recovery codes guardados **apenas como hash** e exibidos em claro **uma única vez**.
- QR Code servido como SVG em `/admin/seguranca/2fa/qr` com `Cache-Control: no-store`.

## 3. Uploads e armazenamento

Fluxo em `DocumentStorageService::store()`:

1. Verifica tipo de documento na whitelist (`ClientRegistration::DOCUMENTS`).
2. Sniffs o **conteúdo real** (`finfo`) — extensão/MIME do cliente são ignorados.
3. `getimagesize` valida dimensões e tipo; limites: `MAX_IMAGE_SIDE=8000` e
   `guardMemory()` (proteção a decompression bombs).
4. Decodifica com GD; rejeita se dimensões decodificadas ≠ cabeçalho.
5. **Re-encode** em JPEG neutro (qualidade 80, `MAX_DIMENSION=1600`), descartando
   EXIF/ICC e payloads extras; orientação EXIF é aplicada antes.
6. Caminho `cadastros/{uuid}/{documento}/{40-caracteres}.jpg` — gerado pelo servidor,
   disco `storage/app/private` (fora da raiz web).

Regras de formulário (`StoreClientRegistrationRequest`): CPF validado (módulo 11) e
único (normalizado antes da validação), idade mínima 18, CEP/telefone por regex,
imagens `required|image|mimes|max:5120|dimensions`, e limite agregado
`UploadBatchMax` (12 MB) por requisição.

Entrega de documentos: Policy `viewDocument` + `isSafePath()` + whitelist por campo;
resposta com `Cache-Control: private, no-store`, jamais pública.

## 4. Auditoria

Tabela imutável `admin_audit_logs` (sem UPDATE/DELETE). `AuditService::log()`
redige chaves sensíveis (cpf, senha, token, biométrico, arquivos, e-mail etc.) antes
da persistência — nunca grava dados pessoais ou conteúdo de documentos.

Eventos: login/logout, 2FA (habilitado/desabilitado/recuperação usada), consentimento,
visualização de cadastro e de documento, alteração de status, exclusão por retenção.
Cada registro guarda usuário (ou null), IP e user agent truncado.

## 5. Retenção & LGPD

- Consentimento registrado no servidor: `privacy_policy_accepted`,
  `privacy_policy_accepted_at`, `privacy_policy_version` (nunca o cliente informa).
- Prazos por status em `config/retention.php` (`RETENTION_*`). O comando:

```bash
php artisan cadastros:expurgo --dry-run   # lista sem apagar
php artisan cadastros:expurgo             # exclui cadastro + documentos + auditoria (idempotente)
```

Ao expurgar, a auditoria de `DeleteRegistration` é gravada **antes** da exclusão
física; o FK aponta para `registration_id` com `ON DELETE SET NULL`.

## 6. Migração SQLite → MySQL

1. Suba o MySQL (Docker) e configure `.env`/`phpunit.xml` (`DB_*`).
2. `php artisan migrate` (novas tabelas/colunas).
3. `php artisan cadastros:import-legado` — importa do SQLite legado, normaliza
   estados/status, reutiliza UUIDs e os documentos já armazenados.

## 7. Backup

```bash
php artisan backup:cadastros [--only=tabela] [--keep=14]
```

- `mysqldump` com `--single-transaction --routines --triggers`, gravado em
  `storage/app/backups/cadastros_<data>.sql`.
- Criptografia **AES-256-CBC** com OpenSSL (`-pbkdf2 -salt`), usando a própria
  `APP_KEY` como passphrase — **a chave do backup não é uma variável separada**;
  guardar `APP_KEY` é o que protege os arquivos `.sql.enc`.
- O `.sql` em claro é removido em seguida; só o `.sql.enc` permanece no disco.
- Rotatória: mantém as N cópias mais recentes (`--keep`, padrão de `config('backup.retention')`).
- Ideal para cron.

## 8. Identidade Visual (VCA)

Sistema atualmente conta com identidade escura própria, aplicada em `public/views/*` e espelhada nos protótipos em `docs/`:

| Token | Valor | Uso |
| --- | --- | --- |
| Superfícies | `#050505`, `#0a0a0a`, `#101010`, `#161616` | fundo de página, painéis, cards |
| Marca | `#fed106` (soft `#ffe15a`, hover `#e9bd05`, active `#c7a205`) | CTA e destaques |
| Bordas | `#242424`, `#3a3a3a` | separadores e inputs |
| Tipografia | Inter | corpo e títulos |
| Logo | `public/images/brand/vca-logo.jpeg` | header e telas públicas/administrativas |

Os protótipos estáticos em `docs/` (11 páginas: formulário público, sucesso, privacidade e área administrativa) seguem essa identidade e servem de referência visual para as telas reais.

## 9. Testes de segurança (resumo)

| Suíte | Cobre |
| --- | --- |
| `SecurityHeadersTest` | CSP sem `unsafe-eval`, `X-Content-Type-Options`, `X-Frame-Options`, `no-store` em documentos |
| `ThrottleTest` | 429 em cadastro/CEP/login e isolamento por e-mail |
| `TwoFactorAuthenticationTest` | fluxo 2FA completo, recovery codes únicos, auditoria, QR protegido |
| `AuditLogTest` | auditoria de views/documentos/status sem dados pessoais |
| `RetentionTest` | dry-run vs expurgo real, idempotência |
| `UploadSecurityTest` | arquivo não-imagem com extensão `.jpg`, dimensões limites, normalização GD, CPF duplicado |
| `CepLookupTest` | resposta controlada, 404 genérico, upstream fora do ar, formato inválido |
| `AdminRegistrationTest` | gerenciamento de cadastros, status transitam, toast único após atualização |
| `Unit/TotpTest` | vetores oficiais RFC 6238, rejeição de códigos inválidos |
| `Unit/LoginThrottleServiceTest` | bloqueio progressivo e isolamento por credencial |

Suíte completa: **141 testes / 602 asserts**, validada em MySQL.

## 10. Checklist de deploy

- [ ] HTTPS em produção + `APP_FORCE_HTTPS=true` e `APP_TRUSTED_PROXIES` corretos
- [ ] `APP_KEY` única e forte em segredo (protege sessões, dados e backups `.enc`)
- [ ] `ADMIN_PASSWORD` removida após `admin:provision` criar o usuário
- [ ] `storage/app/private` fora da raiz web e com permissões restritas
- [ ] Cron: `cadastros:expurgo` (diário) e `backup:cadastros` (diário, com `--keep`)
- [ ] `php artisan test` verde antes de cada release
- [ ] Sem credenciais em arquivos versionados (`.env` gitignored)