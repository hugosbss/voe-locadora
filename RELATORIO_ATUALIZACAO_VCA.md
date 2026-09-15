# Relatório de Atualização — VCA Cadastro

Atualização pré-deploy da aplicação de cadastro de clientes da VCA (branch `vca`).

## 1. O que foi feito

### 1.1 Contrato assinado (entrega final)

- **Assinatura por canvas** na etapa final do formulário público, sem prévia visual da assinatura (requisito: PDF oficial + assinatura + aceite → PDF final assinado).
- **Aceite obrigatório** dos termos antes do envio.
- **Nome do signatário espelhado** do `full_name` (mesma regra validada no backend: `same:full_name`).
- **Geração do PDF final assinado** (FPDF/FPDI) com assinatura embutida, armazenado em área privada por UUID.
- **Preservação do PDF oficial** modelado em `config/contracts.php`, servido de forma controlada (rota `/cadastro/contrato`), nunca pelo webroot público.
- **Rotas administrativas do contrato** por UUID: visualização inline, download do PDF assinado e visualização da assinatura (PNG), sempre com autorização por policy, ownership do arquivo (`ownsStoredContractFile`) e auditoria de acesso.
- **Armazenamento privado** de todos os arquivos (documentos + contrato) em `storage/app/private`, isolados por UUID.
- **Retenção**: o diretório do contrato também é removido pelo serviço de retenção.

### 1.2 CPF duplicado

- Removida a validação `unique` de CPF no `StoreClientRegistrationRequest`.
- Migration aditiva `remove_unique_cpf_on_client_registrations` que remove apenas a constraint (não destrutiva — registros preservados).
- UUID independente e imprevisível para cada cadastro; arquivos privados e PDFs de contrato independentes por UUID.
- Testado no navegador (dois cadastros completos com o mesmo CPF) e por teste funcional no admin (ambos listados, UUIDs distintos).

### 1.3 Fechamento administrativo

- Rotas admin passam a usar `{registration:uuid}` (nunca CPF/nome/e-mail/id na URL).
- Recuperação de senha admin (link por e-mail com identidade VCA), gestão de usuários e notificações por e-mail aos administradores sobre novos cadastros.
- Throttling com dois contadores por IP (envios vs. cadastros criados) e throttling da recuperação de senha.

## 2. Arquivos principais alterados

- `resources/views/client-registrations/create.blade.php`, `resources/js/client-registration.js` — assinatura em canvas, etapa "Contrato e assinatura", revisão/correção e layout desktop (≥768px).
- `app/Services/ContractPdfService.php`, `app/Services/ContractStorageService.php`, `app/Rules/ValidSignatureData.php`, `config/contracts.php` — validação (inclusive assinatura vazia/branca) e geração/armazenamento do contrato.
- `app/Http/Controllers/Public/ClientRegistrationController.php`, `app/Http/Requests/StoreClientRegistrationRequest.php` — fluxo de envio com contrato e CPF sem unique; rota de leitura do modelo; notificação aos admins.
- `app/Services/ClientRegistrationService.php`, `app/Services/RetentionService.php` — criação transacional do cadastro com contrato e limpeza em falha.
- `app/Http/Controllers/Admin/RegistrationController.php`, `app/Policies/ClientRegistrationPolicy.php`, `app/Models/ClientRegistration.php` — rotas por UUID, visualização/baixar/assinatura do contrato, ownership e auditoria.
- `app/Http/Controllers/Admin/PasswordResetLinkController.php`, `NewPasswordController.php`, `UserController.php`, `app/Mail/*`, `app/Services/NewRegistrationNotifier.php`, `resources/views/emails/*`, `resources/views/admin/users/*`, `resources/views/admin/auth/password/*` — recuperação de senha, gestão de usuários e notificações.
- `app/Http/Middleware/ThrottleCadastroSubmissions.php`, `config/rate.php`, `bootstrap/app.php` — throttling de cadastro e de senha.
- `tests/Feature/*` — novos/atualizados: `ClientContractTest`, `AdminPasswordResetTest`, `AdminUsersTest`, `AdminRegistrationTest` (+ teste CPF duplicado), `ClientRegistrationTest`, `UploadSecurityTest`, `ThrottleTest`, `ErrorPagesTest`, `AdminAuthTest`, `AdminEntryPointTest`.
- `resources/css/app.css`, `public/img/logo-vca.jpeg`, `public/images/brand/vca-logo.jpeg`, `config/app.php` — identidade visual VCA e tema do painel.

## 3. Migrations

| Migration | O que faz |
| --- | --- |
| `2026_09_13_031259_backfill_uuids_for_client_registrations` | Preenche UUID para registros históricos sem UUID (chunk `whereNull`, aditivo; `down()` vazio). |
| `2026_09_14_000000_add_contract_columns_to_client_registrations` | Adiciona colunas `contract_*` (nullable/`default false`); `down()` apenas `dropColumn`. |
| `2026_09_15_021839_remove_unique_cpf_on_client_registrations` | Remove a constraint `client_registrations_cpf_unique` (não remove dados); `down()` recria o unique. |

Nenhuma migration usa `DROP DATABASE/TABLE`, `TRUNCATE`, `db:wipe`, `migrate:fresh|refresh` nem remove dados. Não foram executadas em produção.

## 4. Testes / validações

Executados localmente (22/09/2026, sem alterar `.env`):

- `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --compact` → **169 testes / 718 assertions, 100% verde** (inclui fluxos: mobile/desktop, cadastro completo, revisão/correção, assinatura, aceite, CPF inválido, CNH vencida, assinatura vazia/branca rejeitada sem criar registro, CPF duplicado, UUIDs distintos, arquivos privados, PDF assinado, acesso administrativo).
- `vendor/bin/pint --test` → **passed**.
- `npm run build` → **✓ built**.
- `php artisan view:cache` (e `config:cache`) → **OK**.
- Auditoria headless adicional (Chrome CDP, instância SQLite isolada): fluxo 1→7 em 390/430/640/767/768/820/1024/1280/1440/1920px sem overflow; rejeição de assinatura em branco pelo servidor; dois cadastros com o mesmo CPF concluídos com independência de arquivos/PDFs por UUID.

## 5. Segurança

- Uploads e contrato em `storage/app/private`; sem exposição pelo webroot.
- Assinatura validada no servidor (tinta detectada: alpha ≤ 90 e rgb < 660, mínimo 40 px) — PNG sem tinta é rejeitado e nenhum cadastro é criado.
- Ownership por UUID nos arquivos do contrato (caminhos nunca vêm do cliente).
- Rotas admin autenticadas + policy `viewContract` + auditoria (`ViewContract`), URLs por UUID (sem CPF/nome/e-mail).
- Throttle por IP (envios e criações) e na recuperação de senha; CSRF em todos os POSTs.
- Admin: roles, 2FA, gestão de usuários e auditoria de ações; log de falhas de notificação sem bloquear o cadastro.

## 6. Estado final

Validações verdes. Código pronto para a próxima etapa de deploy na Hostinger (o deploy em si não é parte desta tarefa). XAMPP, `.env` e o banco MySQL local permanecem inalterados (18 registros preservados).

## 7. Git

- Branch atual: `vca`
- Remote: `origin` → `https://github.com/hugosbss/voe-locadora.git`
- Commits criados (sem force, sem rebase, sem alteração de histórico):
  - `44d334f` — feat(vca): contrato assinado, CPF duplicado e fechamento administrativo
  - `723486b` — style(vca): identidade visual, ativos de marca e tema da administração
  - *(este relatório)* — docs(vca): relatório da entrega final e estado pré-deploy
- Push: `git push origin vca` — confirmação e estado da branch remota registrados na resposta final.

### Pendências

- Nenhuma pendência funcional. Ficaram fora dos commits apenas artefatos locais de ferramenta de agente (`boost.json`, `.mcp.json`, `AGENTS.md`, `CLAUDE.md`, `briefing.MD` vazio), preservados no working tree.
- Deploy na Hostinger (composer, `.env` de produção, migrations, caches) deliberadamente não executado nesta tarefa.