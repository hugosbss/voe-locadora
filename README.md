# Locadora Veicular — Cadastro de Clientes

Sistema web para cadastro digital de clientes em locadoras de veículos, com envio de
documentação, análise e aprovação por equipe administrativa.

## O problema

O cadastro de novos clientes em locadoras normalmente é feito em papel ou por formulários
genéricos, o que gera:

- erros e dados ilegíveis na digitação manual;
- risco de fraudes de identidade;
- consulta a documentos pela equipe sem rastreabilidade;
- retrabalho na validação de documentos e endereço.

## O que o projeto faz

Um formulário público em **6 etapas** coleta os dados do cliente (pessoais, endereço, CNH),
recebe **fotos dos documentos e selfie**, confere os dados e envia o cadastro para análise.

Na área administrativa, a equipe:

- visualiza a lista de cadastros recebidos, com filtro por status;
- consulta os dados, endereço, CNH e documentos enviados;
- altera o status do cadastro (novo, em análise, aprovado, reprovado);
- acompanha a validação facial (selfie × CNH).

## Destaques

- **Busca de endereço por CEP** via ViaCEP, com preenchimento automático.
- **Validação de CPF** e Form Request com regras de negócio.
- **Upload com câmera/galeria** nos dispositivos móveis e pré-visualização.
- **Máscaras** de CPF, telefone e CEP, com UX mobile-first.
- **Documentos armazenados** em área privada, acessíveis somente com login administrativo.
- **Protótipos estáticos** publicados para demonstração (sem dados reais).

## Stack

- Laravel 13 (PHP 8.5+), MySQL (produção; SQLite legado importado), Tailwind CSS 4, Vite, JavaScript vanilla.

## Estrutura

- `app/Http/Controllers/Public` — cadastro público (formulário e sucesso).
- `app/Http/Controllers/Admin` — autenticação (com 2FA/TOTP) e gestão de cadastros.
- `app/Services` — CEP, armazenamento de documentos, auditoria, retenção e regras de negócio.
- `app/Http/Requests` e `app/Rules` — validações, regra de CPF e limite agregado de upload.
- `config/admin.php`, `config/locations.php`, `config/rate.php`, `config/retention.php`,
  `config/privacy.php` e `config/backup.php` — configurações administrativas e de segurança.
- `docs/` — protótipos estáticos das telas e o manual de operação/segurança.

## Configuração local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Criar o usuário administrador a partir das variáveis `ADMIN_NAME`, `ADMIN_EMAIL` e
`ADMIN_PASSWORD` definidas no seu `.env` (não versionado no repositório):

```bash
php artisan admin:provision
```

A `ADMIN_PASSWORD` (mínimo 12 caracteres) só é usada no seeder/`admin:provision`;
nenhuma senha padrão existe na aplicação.

## Segurança

- **Rate limiting** por IP em cadastro, consulta de CEP e login administrativo, com
  bloqueio progressivo após falhas repetidas.
- **2FA via TOTP (RFC 6238)** com códigos de recuperação (armazenados apenas como hash),
  exigido no login administrativo.
- **Uploads endurecidos**: validação do conteúdo real (finfo + getimagesize + GD), re-encode
  para JPEG neutro (sem EXIF/ICC), limites de dimensão e memória, nomes/caminhos gerados pelo
  servidor e armazenamento fora da raiz web.
- **Autorização granular** (Policies) + **auditoria** de visualizações, documentos, mudanças de
  status e eventos de autenticação (metadados sensíveis redigidos).
- **Retenção e LGPD**: consentimento registrado com versão da Política de Privacidade e
  timestamps definidos pelo servidor; expurgo automático dos cadastros após o prazo por status.
- **Headers de segurança** (CSP, X-Frame-Options, HSTS opcional, etc.), cookies HttpOnly/Secure,
  sessão regenerada no login/logout e respostas de documentos com `Cache-Control: private, no-store`.

## Operação

```bash
# Backups criptografados (AES-256-CBC) com rotatória
php artisan backup:cadastros

# Expurgo de cadastros com retenção expirada (preview seguro)
php artisan cadastros:expurgo --dry-run
php artisan cadastros:expurgo

# Migração de dados do SQLite legado para o MySQL
php artisan cadastros:import-legado
```

Agende `cadastros:expurgo` e `backup:cadastros` em cron. Detalhes completos (variáveis,
prazos, exemplos) no manual: [docs/seguranca-e-operacao.md](docs/seguranca-e-operacao.md).

## Testes

```bash
php artisan test
```

A suíte (68 testes) roda contra MySQL de teste e cobre autenticação, autorização, auditoria,
retenção, throttling, TOTP (vetores RFC 6238), uploads e proteção de CEP.

## Protótipos

As telas do sistema estão disponíveis como protótipo estático em:

**https://hugosbss.github.io/voe-locadora/**

> Os protótipos são apenas demonstração visual — nenhum dado real é enviado.