<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sms\HandleSmsWebhookRequest;
use App\Services\Sms\SmsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SmsWebhookController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    public function store(HandleSmsWebhookRequest $request): JsonResponse
    {
        $record = $this->smsService->processIncoming($request->input('phone'), $request->input('body'));

        return ApiResponse::success([
            'id' => $record->id,
            'status' => $record->status,
        ]);
    }
}
