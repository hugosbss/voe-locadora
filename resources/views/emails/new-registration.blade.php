<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo cadastro recebido</title>
    <style>
        body, table, td, p, a { font-family: Helvetica, Arial, sans-serif; }
        .vca-btn:hover { background-color: #e0b800; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f4f5; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;">
                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background-color:#0a0a0a; border-radius:12px 12px 0 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding:20px 28px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <img src="{{ $message->embed(public_path('images/brand/vca-logo.jpeg')) }}" alt="{{ $brandName }}" width="44" height="44" style="border-radius:10px; display:block;" />
                                                </td>
                                                <td style="padding-left:12px; vertical-align:middle;">
                                                    <span style="font-size:20px; font-weight:700; color:#FED106; letter-spacing:1px;">{{ $brandName }}</span>
                                                    <span style="display:block; font-size:12px; color:#a1a1aa; margin-top:2px;">Cadastro de clientes</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Corpo --}}
                    <tr>
                        <td style="background-color:#ffffff; padding:32px 28px;">
                            <h1 style="margin:0 0 6px; font-size:22px; line-height:1.3; color:#111111;">Novo cadastro recebido</h1>
                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#52525b;">
                                Um novo cadastro foi enviado e está aguardando análise.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e4e4e7;">
                                <tr>
                                    <td style="padding:14px 0 4px; font-size:12px; color:#71717a;">Cliente</td>
                                    <td align="right" style="padding:14px 0 4px; font-size:14px; font-weight:600; color:#0a0a0a;">{{ $name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#71717a;">Data de envio</td>
                                    <td align="right" style="padding:6px 0; font-size:14px; color:#18181b;">{{ $date }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0 22px; font-size:12px; color:#71717a;">Status</td>
                                    <td align="right" style="padding:6px 0 22px; font-size:14px; font-weight:600; color:#0a0a0a;">{{ $statusLabel }}</td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="padding-top:8px;">
                                        <a href="{{ $url }}"
                                            class="vca-btn"
                                            style="display:inline-block; padding:12px 28px; border-radius:8px; background-color:#FED106; color:#0a0a0a; font-size:14px; font-weight:700; text-decoration:none;">
                                            Ver cadastro
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="background-color:#0a0a0a; border-radius:0 0 12px 12px; padding:16px 28px;">
                            <p style="margin:0; font-size:11px; line-height:1.5; color:#71717a;">
                                {{ $brandName }}.
                                Acesse o painel para revisar este cadastro.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>