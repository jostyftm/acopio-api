<?php

namespace App\Services\Registration;

use App\Enums\RegistrationSource;
use App\Models\Person;
use Illuminate\Support\Str;

class RegistrationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Person
    {
        $existing = $this->findDuplicate($data['document_type'], $data['document_number']);

        if ($existing !== null) {
            return $existing;
        }

        return Person::query()->create([
            ...$data,
            'source' => $data['source'] ?? RegistrationSource::Web,
            'data_consent' => true,
        ]);
    }

    public function findDuplicate(string $documentType, string $documentNumber): ?Person
    {
        return Person::query()
            ->where('document_type', $documentType)
            ->where('document_number', Str::upper($documentNumber))
            ->first();
    }
}
