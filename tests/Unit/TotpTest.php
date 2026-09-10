<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_generates_consistent_secret(): void
    {
        $totp = new Totp;

        $secret = $totp->generateSecret();

        $this->assertGreaterThanOrEqual(26, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_verify_accepts_current_code(): void
    {
        $totp = new Totp;
        $secret = $totp->generateSecret();

        $code = $totp->codeAt((int) floor(time() / 30), $secret);

        $this->assertTrue($totp->verify($code, $secret));
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $totp = new Totp;
        $secret = $totp->generateSecret();

        $this->assertFalse($totp->verify('000000', $secret));
    }

    public function test_verify_rejects_non_numeric_input(): void
    {
        $this->assertFalse((new Totp)->verify('<script>', 'A'.str_repeat('B', 25)));
    }

    public function test_verify_accepts_code_within_clock_drift(): void
    {
        $totp = new Totp;
        $secret = $totp->generateSecret();

        $past = $totp->codeAt((int) floor(time() / 30) - 2, $secret);

        $this->assertTrue($totp->verify($past, $secret));
    }

    #[DataProvider('rfc6238Vectors')]
    public function test_rfc6238_sha1_vectors(int $counter, string $expected): void
    {
        // Segredo RFC 6238: o texto "12345678901234567890" como bytes,
        // codificado em base32 para a implementação.
        $ascii = '12345678901234567890';
        $base32 = $this->base32Encode($ascii);

        $this->assertSame($expected, (new Totp)->codeAt($counter, $base32));
    }

    public static function rfc6238Vectors(): array
    {
        return [
            // T em segundos -> contador = floor(T / 30).
            [1, '287082'],
            [37037036, '081804'],
            [37037037, '050471'],
        ];
    }

    private function base32Encode(string $bytes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) === 5) {
                $encoded .= $alphabet[bindec($chunk)];
            }
        }

        return $encoded;
    }
}
