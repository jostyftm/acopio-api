<?php

use App\Exceptions\ApiHandlerException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

it('renders throttle exceptions as a 429 with a friendly rate-limit message', function () {
    $response = ApiHandlerException::render(new ThrottleRequestsException);

    expect($response->getStatusCode())->toBe(429)
        ->and($response->getData(true))->toMatchArray([
            'success' => false,
            'message' => __('messages.rate_limited'),
            'errors' => [],
        ]);
});
