<?php

namespace App\Exceptions;

use Exception;

/**
 * Exceção de domínio lançada quando um cadastro não pode reservar a cota
 * solicitada (tipo indisponível para o veículo, período com duração inválida
 * ou sem vagas no período).
 *
 * IMPORTANTE: não estende RuntimeException de propósito. O fluxo de gravação
 * do cadastro público captura RuntimeException para tratar falha de assinatura
 * de imagens; se esta exceção herdasse dela, viraria um 500 genérico em vez
 * de um erro de validação amigável (422).
 */
class QuotaUnavailableException extends Exception
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(): self
    {
        return new self(
            'quota_type_id',
            'Esta cota não está disponível para o veículo selecionado.',
        );
    }

    public static function invalidDuration(): self
    {
        return new self(
            'end_date',
            'A duração informada não é compatível com este tipo de cota.',
        );
    }

    public static function unavailable(): self
    {
        return new self(
            'quota_type_id',
            'Sem vagas para o período selecionado.',
        );
    }
}
