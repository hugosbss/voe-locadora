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

- Laravel 13 (PHP), SQLite (desenvolvimento), Tailwind CSS 4, Vite, JavaScript vanilla.

## Estrutura

- `app/Http/Controllers/Public` — cadastro público (formulário e sucesso).
- `app/Http/Controllers/Admin` — autenticação e gestão de cadastros.
- `app/Services` — CEP, armazenamento de documentos, validação facial e regras de negócio.
- `app/Http/Requests` e `app/Rules` — validações e regra de CPF.
- `config/admin.php` e `config/locations.php` — configurações administrativas e estados/CEP.
- `docs/` — protótipos estáticos das telas, publicados no GitHub Pages.

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

Criar o usuário administrador (`AdminUserSeeder`) a partir das variáveis
`ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` definidas no seu `.env`
(não versionado no repositório).

## Testes

```bash
php artisan test
```

## Protótipos

As telas do sistema estão disponíveis como protótipo estático em:

**https://hugosbss.github.io/locadora-veicular/**

> Os protótipos são apenas demonstração visual — nenhum dado real é enviado.