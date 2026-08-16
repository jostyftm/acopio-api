<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class EvidenceFile implements ValidationRule
{
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
            $maxBytes = (int) config('evidence.max_image_bytes');
            if ($size > $maxBytes) {
                $fail('La foto no puede superar los '.intdiv($maxBytes, 1024 * 1024).'MB.');
            }

            return;
        }

        if (str_starts_with($mime, 'video/')) {
            $maxBytes = (int) config('evidence.max_video_bytes');
            if ($size > $maxBytes) {
                $fail('El video no puede superar los '.intdiv($maxBytes, 1024 * 1024).'MB.');
            }

            return;
        }

        $fail('El archivo debe ser una foto o un video.');
    }
}
