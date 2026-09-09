@extends('layouts.public')

@section('title', 'Política de Privacidade')

@section('content')
    <div class="card p-6 sm:p-8">
        <h1 class="mb-4 text-xl font-semibold tracking-tight text-slate-900">Política de Privacidade</h1>

        <div class="space-y-5 text-sm leading-relaxed text-slate-600">
            <p>
                Ao preencher o cadastro, você fornece dados pessoais (nome, CPF, data de nascimento,
                telefone, WhatsApp, e-mail), dados de endereço, dados da CNH (número, categoria e validade)
                e documentos como foto da CNH, comprovante de residência e selfie.
            </p>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-slate-900">Uso dos dados</h2>
                <p>
                    Os dados e documentos informados são utilizados exclusivamente para
                    <strong>análise da sua locação</strong> pela equipe da locadora, incluindo a
                    conferência de identidade, habilitação para dirigir e residência.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-slate-900">Armazenamento e segurança</h2>
                <p>
                    As imagens e documentos são armazenados em área privada, com acesso restrito a
                    pessoas autorizadas da empresa, e protegidos por login administrativo.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-slate-900">Compartilhamento</h2>
                <p>
                    Os dados não são vendidos nem compartilhados com terceiros para fins comerciais.
                </p>
            </div>

            <div>
                <h2 class="mb-1 text-sm font-semibold text-slate-900">Retenção</h2>
                <p>
                    Os dados são mantidos pelo tempo necessário à prestação do serviço e cumprimento de
                    obrigações legais.
                </p>
            </div>
        </div>
    </div>
@endsection