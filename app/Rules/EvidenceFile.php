<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class EvidenceFile implements ValidationRule
{
    private const IMAGE_MAX_BYTES = 10 * 1024 * 1024;

    private const VIDEO_MAX_BYTES = 100 * 1024 * 1024;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): mixed  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('El archivo no es válido.');

            return;
        }

        $mime = $value->getMimeType() ?? '';
        $size = $value->getSize();

        if (str_starts_with($mime, 'image/')) {
            if ($size > self::IMAGE_MAX_BYTES) {
                $fail('La foto no puede superar los 10MB.');
            }

            return;
        }

        if (str_starts_with($mime, 'video/')) {
            if ($size > self::VIDEO_MAX_BYTES) {
                $fail('El video no puede superar los 100MB.');
            }

            return;
        }

        $fail('El archivo debe ser una foto o un video.');
    }
}
