<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FittService
{
    private string $baseUrl;

    private string $merchantId;

    private string $paymentKey;

    private string $currency;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.flitt.base_url');
        $this->merchantId = (string) config('services.flitt.merchant_id');
        $this->paymentKey = (string) config('services.flitt.key');
        $this->currency = (string) config('services.flitt.currency');

        if ($this->merchantId === '' || $this->paymentKey === '') {
            throw new RuntimeException('fitt EUR merchant credentials are missing.');
        }
    }

    public function currency(): string
    {
        return (string) config('services.flitt.currency');
    }

    public function createCheckout(array $parameters): array
    {
        return $this->request('/api/checkout/url', $parameters);
    }

    public function stopSubscription(string $orderId): void
    {
        $response = $this->request('/api/subscription', ['order_id' => $orderId, 'action' => 'stop']);
        if (($response['status'] ?? '') !== 'disabled') {
            throw new RuntimeException('Flitt has not confirmed cancellation.');
        }
    }

    public function chargeRecurring(array $parameters): array
    {
        return $this->request('/api/recurring', $parameters);
    }

    public function getOrderStatus(string $orderId): array
    {
        return $this->request('/api/status/order_id', [
            'version' => '1.0.1',
            'order_id' => $orderId,
        ]);
    }

    public function toMinorUnits(mixed $amount): int
    {
        if (! is_numeric($amount)) {
            throw new RuntimeException('Invalid fitt amount.');
        }

        $minor = (int) round(((float) $amount) * 100);

        if ($minor <= 0) {
            throw new RuntimeException('fitt amount must be greater than zero.');
        }

        return $minor;
    }

    public function fromMinorUnits(mixed $amount): string
    {
        if (! is_numeric($amount)) {
            throw new RuntimeException('Invalid fitt minor-unit amount.');
        }

        return number_format(((int) $amount) / 100, 2, '.', '');
    }

    public function generateSignature(array $parameters): string
    {
        unset(
            $parameters['signature'],
            $parameters['response_signature_string']
        );

        $parameters = array_filter(
            $parameters,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        ksort($parameters, SORT_STRING);

        $values = [$this->paymentKey];

        foreach ($parameters as $value) {
            if (is_bool($value)) {
                $values[] = $value ? '1' : '0';

                continue;
            }

            $values[] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : (string) $value;
        }

        return sha1(implode('|', $values));
    }

    public function verifySignature(array $payload): bool
    {
        $received = (string) ($payload['signature'] ?? '');

        if ($received === '') {
            return false;
        }

        return hash_equals(
            $this->generateSignature($payload),
            $received
        );
    }

    public function additionalInfo(array $payload): array
    {
        $value = $payload['additional_info'] ?? [];

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function issuerCountry(array $payload): ?string
    {
        $country = strtoupper(trim((string) data_get(
            $this->additionalInfo($payload),
            'bank_country',
            ''
        )));

        return $country !== '' ? $country : null;
    }

    public function isGeorgianIssuer(?string $country): bool
    {
        return in_array(
            strtoupper(trim((string) $country)),
            ['GE', 'GEO', 'GEORGIA'],
            true
        );
    }

    public function isCardPayment(array $payload): bool
    {
        $method = strtolower(trim((string) (
            $payload['payment_system']
            ?? data_get($this->additionalInfo($payload), 'payment_method', '')
        )));

        return $method === 'card';
    }

    private function request(string $endpoint, array $parameters): array
    {
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $parameters[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            }
        }
        $parameters['merchant_id'] = $this->merchantId;
        $parameters['signature'] = $this->generateSignature($parameters);

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($this->baseUrl.$endpoint, [
                'request' => $parameters,
            ]);

        $this->assertHttpSuccess($response);

        $data = $response->json('response');

        if (! is_array($data)) {
            throw new RuntimeException('Invalid response received from fitt.');
        }

        if (($data['response_status'] ?? null) === 'failure') {
            throw new RuntimeException(
                (string) ($data['error_message']
                    ?? $data['response_description']
                    ?? 'fitt request failed.')
            );
        }

        if (! $this->verifySignature($data)) {
            throw new RuntimeException('Invalid fitt response signature.');
        }

        return $data;
    }

    private function assertHttpSuccess(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Flitt HTTP request failed with status %d',
            $response->status()
        ));
    }
}
