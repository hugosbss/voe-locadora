<?php

namespace App\Exceptions;

use Exception;

/**
 * Exceção de domínio lançada quando um link de assinatura não pode ser usado:
 * o cadastro ainda não está aprovado ou a assinatura já foi registrada.
 *
 * IMPORTANTE: não estende RuntimeException de propósito. O fluxo de
 * assinatura captura RuntimeException para apagar os arquivos gravados na
 * tentativa; como esta exceção é lançada ANTES de qualquer escrita, ela
 * nunca deve ser tratada como falha de armazenamento (e jamais deve apagar o
 * contrato assinado que já existe no cadastro).
 */
class ContractSignatureNotAllowedException extends Exception
{
    public static function notApproved(): self
    {
        return new self('Este cadastro não está liberado para assinatura.');
    }

    public static function alreadySigned(): self
    {
        return new self('Este contrato já foi assinado.');
    }
}
