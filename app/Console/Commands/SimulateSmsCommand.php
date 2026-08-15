<?php

namespace App\Console\Commands;

use App\Services\Sms\SmsService;
use Illuminate\Console\Command;

class SimulateSmsCommand extends Command
{
    protected $signature = 'acopio:sms:simulate
        {phone : Origin phone number, e.g. 573001234567}
        {body : Raw SMS body, e.g. REG, Maria Garcia, CC, 123456789, Buenaventura}';

    protected $description = 'Simulate an incoming SMS to test the registration flow without a real provider.';

    public function handle(SmsService $smsService): int
    {
        $record = $smsService->processIncoming($this->argument('phone'), $this->argument('body'));

        $this->components->info("SMS record processed with status [{$record->status}].");

        if ($record->status === 'failed') {
            $this->components->error($record->error);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
