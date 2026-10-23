<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaypalService
{
    public function client(?string $idempotencyKey = null): PendingRequest
    {
        if (blank(config('services.paypal.client_id')) || blank(config('services.paypal.secret'))) {
            throw new RuntimeException('PayPal credentials are missing.');
        }
        $token = Http::asForm()->timeout(20)->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post(config('services.paypal.base_url').'/v1/oauth2/token', ['grant_type' => 'client_credentials'])->throw()->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('PayPal did not return an access token.');
        }

        return Http::baseUrl(config('services.paypal.base_url'))->withToken($token)->acceptJson()->asJson()->timeout(20)
            ->withHeaders($idempotencyKey ? ['PayPal-Request-Id' => $idempotencyKey] : []);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function create(array $payload, string $key): array
    {
        return $this->client($key)->post('/v1/billing/subscriptions', $payload)->throw()->json();
    }

    /** @return array<string, mixed> */
    public function details(string $id): array
    {
        return $this->client()->get('/v1/billing/subscriptions/'.rawurlencode($id))->throw()->json();
    }

    public function cancel(string $id, string $key): void
    {
        if (($this->details($id)['status'] ?? '') === 'CANCELLED') {
            return;
        }
        $this->client($key)->post('/v1/billing/subscriptions/'.rawurlencode($id).'/cancel', ['reason' => 'Customer disabled AutoPay.'])->throw();
        if (($this->details($id)['status'] ?? '') !== 'CANCELLED') {
            throw new RuntimeException('PayPal has not confirmed cancellation.');
        }
    }

    public function verify(Request $request): bool
    {
        if (blank(config('services.paypal.webhook_id'))) {
            return false;
        }
        $headers = ['auth_algo' => 'PAYPAL-AUTH-ALGO', 'cert_url' => 'PAYPAL-CERT-URL',
            'transmission_id' => 'PAYPAL-TRANSMISSION-ID', 'transmission_sig' => 'PAYPAL-TRANSMISSION-SIG',
            'transmission_time' => 'PAYPAL-TRANSMISSION-TIME'];
        $payload = ['webhook_id' => config('services.paypal.webhook_id'), 'webhook_event' => $request->json()->all()];
        foreach ($headers as $key => $header) {
            if (! $request->hasHeader($header)) {
                return false;
            }
            $payload[$key] = $request->header($header);
        }

        return $this->client()->post('/v1/notifications/verify-webhook-signature', $payload)->throw()->json('verification_status') === 'SUCCESS';
    }
}
