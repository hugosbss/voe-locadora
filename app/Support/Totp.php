<?php

namespace App\Support;

use RuntimeException;

/**
 * Implementação de TOTP (RFC 6238) com HMAC-SHA1 — compatível com
 * autenticadores padrão (Google Authenticator, Authy, 1Password etc.).
 *
 * Nenhuma dependência externa: usa apenas funções nativas do PHP.
 */
class Totp
{
    private const TIME_STEP = 30;

    private const DIGITS = 6;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $query = http_build_query([
            'secret' => strtoupper($secret),
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::TIME_STEP,
        ]);

        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.$query;
    }

    public function verify(string $code, string $secret, int $window = 2): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if ($code === null || preg_match('/^\d{6}$/', $code) !== 1) {
            return false;
        }

        $counter = (int) floor(time() / self::TIME_STEP);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $expected = $this->codeAt($counter + $offset, $secret);

            // Vírgula é constante? Não: hash_equals é constante para
            // comprimentos iguais, que é o caso aqui.
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    public function codeAt(int $counter, string $secret): string
    {
        $binary = $this->base32Decode($secret);

        if ($binary === '') {
            throw new RuntimeException('Segredo TOTP inválido.');
        }

        // Contador de 8 bytes em big-endian (RFC 4226/HOTP). O PHP lida com
        // inteiros de 64 bits nativamente, então o registrador alto é 0.
        $hash = hash_hmac('sha1', pack('N2', 0, $counter), $binary, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binaryCode =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binaryCode % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret) ?? '');
        $bits = '';
        $output = '';

        foreach (str_split($secret) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }

    private function base32Encode(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) === 5) {
                $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
            }
        }

        return $encoded;
    }
}
