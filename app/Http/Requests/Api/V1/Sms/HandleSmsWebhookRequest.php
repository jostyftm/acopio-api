<?php

namespace App\Http\Requests\Api\V1\Sms;

use Illuminate\Foundation\Http\FormRequest;

class HandleSmsWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->header('X-Webhook-Secret') === (string) config('services.sms.webhook_secret');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /**
             * Phone number that sent the SMS.
             *
             * @example 573001234567
             */
            'phone' => ['required', 'string', 'max:24'],

            /**
             * Raw body of the incoming SMS.
             *
             * @example REG, Maria Garcia, CC, 123456789, Buenaventura
             */
            'body' => ['required', 'string', 'max:1600'],
        ];
    }
}
