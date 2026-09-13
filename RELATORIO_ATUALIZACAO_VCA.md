# Relatório de Atualização VCA

Registro das entregas realizadas na branch `vca`, com foco em correções de UI,
documentação e identidade visual.

---

## 1. Correção: toast de atualização de status

**Commit:** `9f4936a` — `fix(vca): consumir toast de atualização de status`

### Sintoma

Depois de alterar o status de um cadastro, o toast de confirmação ("Status atualizado")
reaparecia a cada nova renderização da lista — ao aplicar filtro de status, trocar de
página ou recarregar o navegador. O indicador `status_updated=1` ficava preso na sessão.

### Causa

A flag `status_updated` era gravada na sessão no POST de atualização, mas a leitura
ocorria no mesmo controller sem nunca ser consumida (removida), deixando o toast
"eterno".

### Correção

- `App\Http\Controllers\Admin\RegistrationController::index()` passou a consumir a flag
  **uma única vez**: ao detectar `has('status_updated')`, remonta a URL com os mesmos
  parâmetros de filtro/paginação e redireciona (PRG), liberando a flag da sessão.
- O retorno do método tornou-se `View|RedirectResponse` para refletir os dois fluxos.
- Testes cobrem que o toast aparece exatamente uma vez e não reaparece ao filtrar,
  paginar ou recarregar.

### Validação

- Suíte completa: 141 testes / 602 asserts, verde em MySQL.
- Headless browser: o toast aparece uma única vez e não se repete após navegação.
- Banco devolvido ao estado original (18 registros de cadastro).

---

## 2. Documentação e identidade visual

**Commit:** `9755c98` — `docs(vca): atualizar documentação e identidade visual`

### O que foi feito

- **Protótipos migrados para a identidade VCA** (`docs/`): as 10 páginas anteriores
  (identidade clara/azul da época de mockup) foram reconstruídas no tema escuro oficial
  e ganharam uma nova página: **`admin-users.html`** (gestão de usuários). Total: **11
  páginas** — `index`, `form` (6 etapas), `sucesso`, `privacidade`, `admin-login`,
  `admin-dashboard`, `admin-list` (filtro + tabela + paginação), `admin-detalhe`
  (endereço, CNH, documentos em lightbox, status e facial), `admin-link`, `admin-users`,
  `admin-seguranca` (2FA).
- **Novo design system dos protótipos**: `docs/assets/css/style.css` (tokens da marca,
  superfícies `#050505`–`#161616`, destaque `#fed106`, tipografia Inter) e
  `docs/assets/js/main.js` (copiar link, compartilhar, mostrar/ocultar senha, menu mobile).
- **Logo real** copiada para `docs/assets/img/vca-logo.jpeg` (`public/images/brand/vca-logo.jpeg`).
- **Revisão do manual** `docs/seguranca-e-operacao.md`:
  - Corrigidas as variáveis de rate limiting para os nomes reais
    (`RATE_LIMIT_CADASTRO_MAX_ATTEMPTS`, `RATE_LIMIT_CADASTRO_SUCCESS_MAX_ATTEMPTS`,
    `RATE_LIMIT_CADASTRO_DECAY_MINUTES`, `RATE_LIMIT_CADASTRO_SUCCESS_DECAY_MINUTES`,
    `RATE_LIMIT_PASSWORD_RESET`, `RATE_LIMIT_PASSWORD_RESET_DECAY_MINUTES`).
  - Backup: esclarecido que a criptografia AES-256-CBC usa a própria **`APP_KEY`**
    (não existe `BACKUP_ENCRYPTION_KEY`); opções `--only`/`--keep`; rotatória de cópias.
  - Nova seção **Identidade Visual (VCA)** e alinhamento da suíte de testes
    (141 testes / 602 asserts).
- **`README.md` atualizado** com o estado real: stack (Laravel 13 `^13.17`, PHP `^8.3`,
  MySQL, Tailwind 4, Vite), features atuais (dashboard, link de cadastro, usuários,
  recuperação de senha, notificação por e-mail, toast único), operação (backup, expurgo,
  migração legado) e referência aos protótipos com identidade VCA.
- **Limpeza**: removidos `.agents/`, `.claude/` e `opencode.json`. O `.mcp.json` foi
  **mantido** (intencional).

### Validação

- Protótipos validados em headless (Playwright/Chromium) via `file://`:
  **11/11 páginas OK** — fundo escuro, fonte Inter carregada, logo sem quebra, zero erros
  de console/página.
- `php artisan test` verde; `pint` sem pendências nos arquivos tocados.

---

## Resumo Git

```
9755c98 docs(vca): atualizar documentação e identidade visual
9f4936a fix(vca): consumir toast de atualização de status
dd0bbc2 docs: relatório de atualização VCA (fotos e documentos)
```

Branch: `vca` · Remote: `origin` (`hugosbss/voe-locadora`)