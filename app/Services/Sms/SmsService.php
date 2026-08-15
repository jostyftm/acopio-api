<?php

namespace App\Services\Sms;

use App\Enums\RegistrationSource;
use App\Models\SmsRecord;
use App\Services\Registration\RegistrationService;
use Illuminate\Support\Str;
use Throwable;

class SmsService
{
    public function __construct(
        private readonly SmsProvider $provider,
        private readonly RegistrationService $registrationService,
    ) {}

    public function send(string $phone, string $message): void
    {
        $this->provider->send($phone, $message);

        SmsRecord::query()->create([
            'origin_phone' => $phone,
            'body' => $message,
            'direction' => 'outgoing',
            'status' => 'sent',
            'processed_at' => now(),
        ]);
    }

    public function processIncoming(string $phone, string $body): SmsRecord
    {
        $record = SmsRecord::query()->create([
            'origin_phone' => $phone,
            'body' => $body,
            'direction' => 'incoming',
            'status' => 'pending',
        ]);

        try {
            $this->handleCommand($record);
        } catch (Throwable $e) {
            $record->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }

        return $record->fresh();
    }

    private function handleCommand(SmsRecord $record): void
    {
        $parts = $this->parse($record->body);
        $command = Str::upper(array_shift($parts) ?? '');

        if ($command === 'REG') {
            $this->registerViaSms($record, $parts);

            return;
        }

        $this->send(
            $record->origin_phone,
            'Unrecognized command. To register, send: REG, FirstName LastName, DocType, DocNumber, Municipality, Neighborhood',
        );
        $record->update(['status' => 'processed', 'processed_at' => now()]);
    }

    /**
     * @return array<int, string>
     */
    private function parse(string $body): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($body)) ?? '';

        return array_map('trim', explode(',', $normalized));
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function registerViaSms(SmsRecord $record, array $fields): void
    {
        $nameParts = preg_split('/\s+/', trim($fields[0] ?? ''), 2) ?: [];

        $data = [
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'document_type' => Str::upper($fields[1] ?? 'OTHER'),
            'document_number' => preg_replace('/[^0-9A-Za-z]/', '', $fields[2] ?? ''),
            'municipality' => $fields[3] ?? 'Undefined',
            'neighborhood' => $fields[4] ?? null,
            'phone' => $record->origin_phone,
            'source' => RegistrationSource::Sms,
        ];

        $person = $this->registrationService->register($data);

        $record->update([
            'person_id' => $person->id,
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        $this->send(
            $record->origin_phone,
            "Registration received. Your case number is {$person->id}. Reply with your location to update it.",
        );
    }
}
