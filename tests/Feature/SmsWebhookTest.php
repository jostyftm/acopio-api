<?php

use App\Models\SmsRecord;

it('rejects webhook requests without the correct secret', function () {
    $this->postJson('/api/v1/sms/webhook', [
        'phone' => '3001234567',
        'body' => 'REG, Maria Garcia, CC, 123456789, Buenaventura',
    ], ['X-Webhook-Secret' => 'wrong-secret'])->assertStatus(403);
});

it('registers a person from an incoming REG sms command', function () {
    $secret = config('services.sms.webhook_secret');

    $this->postJson('/api/v1/sms/webhook', [
        'phone' => '3001234567',
        'body' => 'REG, Maria Garcia, CC, 123456789, Buenaventura, La Playita',
    ], ['X-Webhook-Secret' => $secret])
        ->assertOk()
        ->assertJsonPath('data.status', 'processed');

    $this->assertDatabaseHas('people', [
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'document_number' => '123456789',
        'source' => 'sms',
    ]);

    $record = SmsRecord::first();
    expect($record)->not->toBeNull();
    expect($record->person_id)->not->toBeNull();
});

it('rejects sms webhook requests without the secret header', function () {
    $this->postJson('/api/v1/sms/webhook', [
        'phone' => '3001234567',
        'body' => 'REG, Maria Garcia, CC, 123456789, Buenaventura',
    ])->assertStatus(403);
});

it('marks unknown sms commands as processed and replies with help', function () {
    $secret = config('services.sms.webhook_secret');

    $this->postJson('/api/v1/sms/webhook', [
        'phone' => '3001234567',
        'body' => 'HELP',
    ], ['X-Webhook-Secret' => $secret])
        ->assertOk()
        ->assertJsonPath('data.status', 'processed');

    $this->assertDatabaseHas('sms_records', [
        'direction' => 'outgoing',
        'status' => 'sent',
    ]);
});
