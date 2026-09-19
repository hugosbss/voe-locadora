@extends('layouts.public')

@section('title', 'Política de Privacidade')

@section('content')
    <div class="card panel-card p-6 sm:p-8">
        <h1 class="mb-4 text-xl font-semibold tracking-tight text-white">Política de Privacidade</h1>

        <div class="mb-5 space-y-2 border-b border-line-dark pb-5 text-xs text-zinc-500">
            <p>Versão {{ config('privacy.version') }} &middot; Publicada em {{ now()->format('d/m/Y') }}</p>
            <p>Este documento descreve como seus dados são tratados em conformidade com a LGPD (Lei nº 13.709/2018).</p>
        </div>

        <div class="space-y-5 text-sm leading-relaxed text-zinc-300">
            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">1. Quais dados coletamos</h2>
                <p>
                    Ao preencher o cadastro, você fornece dados pessoais (nome, CPF, data de nascimento,
                    telefone, WhatsApp, e-mail), dados de endereço, dados da CNH (número, categoria e validade)
                    e documentos como foto da CNH, comprovante de residência e selfie para conferência de identidade.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">2. Uso dos dados</h2>
                <p>
                    Os dados e documentos informados são utilizados exclusivamente para
                    <strong>análise da sua locação</strong> pela equipe da locadora, incluindo a
                    conferência de identidade, habilitação para dirigir e residência.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">3. Consentimento</h2>
                <p>
                    Ao confirmar este cadastro, você declara que as informações fornecidas são verdadeiras e
                    autoriza a locadora a utilizar seus dados e documentos exclusivamente para a análise desta
                    locação. A data e a versão da política aceitas são registradas de forma segura e conferíveis.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">4. Armazenamento e segurança</h2>
                <p>
                    As imagens e documentos são armazenados em área privada e criptografada de acesso restrito,
                    com verificação em duas etapas nos acessos administrativos e registro de auditoria de cada
                    visualização. Conexões são protegidas por HTTPS e os dados em repouso são criptografados.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">5. Cookies e sessão</h2>
                <p>
                    Este site usa apenas cookies estritamente necessários: o cookie de sessão (que mantém as
                    respostas do formulário durante o preenchimento e a sessão administrativa) e o token CSRF
                    de proteção do formulário. <strong>Não utilizamos cookies de publicidade ou rastreamento.</strong>
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">6. Compartilhamento</h2>
                <p>
                    Os dados não são vendidos nem compartilhados com terceiros para fins comerciais.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">7. Retenção e exclusão</h2>
                <p>
                    Os dados são mantidos somente pelo tempo previsto em nossa política interna de retenção,
                    conforme o andamento da análise. Findo o prazo, os dados e as cópias dos documentos são
                    eliminados automaticamente.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-brand">8. Seus direitos</h2>
                <p>
                    Você pode solicitar a confirmação, correção ou eliminação dos seus dados a qualquer momento.
                    Solicitações são tratadas em até 15 dias úteis.
                </p>
            </div>
        </div>
    </div>
@endsection