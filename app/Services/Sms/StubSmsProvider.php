<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class StubSmsProvider implements SmsProvider
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS sent (stub)', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
