<?php

namespace Tests\Unit;

use App\Rules\UploadBatchMax;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UploadBatchMaxTest extends TestCase
{
    private array $fields = ['a_file', 'b_file', 'c_file'];

    public function test_passes_when_total_is_within_limit(): void
    {
        $this->inebriateRequest([
            'a_file' => UploadedFile::fake()->create('a.jpg', 300),
            'b_file' => UploadedFile::fake()->create('b.jpg', 300),
            'c_file' => UploadedFile::fake()->create('c.jpg', 300),
        ]);

        $validator = Validator::make(
            ['a_file' => UploadedFile::fake()->create('a.jpg', 300)],
            ['a_file' => new UploadBatchMax($this->fields, 2000)],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_total_exceeds_limit_per_file_but_aggregated_ok(): void
    {
        $this->inebriateRequest([
            'a_file' => UploadedFile::fake()->create('a.jpg', 2000),
            'b_file' => UploadedFile::fake()->create('b.jpg', 2000),
        ]);

        // Cada arquivo tem 2 MB: sob o limite individual, mas o total de 4 MB
        // excede o limite agregado de 3 MB.
        $validator = Validator::make(
            ['a_file' => UploadedFile::fake()->create('a.jpg', 2000)],
            ['a_file' => new UploadBatchMax($this->fields, 3072)],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('total dos arquivos', $validator->errors()->first('a_file'));
    }

    public function test_ignores_non_upload_input(): void
    {
        $validator = Validator::make(
            ['a_file' => 'not-a-file'],
            ['a_file' => new UploadBatchMax($this->fields, 1000)],
        );

        $this->assertTrue($validator->passes());
    }

    /**
     * Simula o request contendo os arquivos do lote.
     */
    private function inebriateRequest(array $files): void
    {
        $request = new Request;
        $request->files->replace($files);

        app()->instance('request', $request);
    }
}
