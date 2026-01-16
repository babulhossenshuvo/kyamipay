<?php

namespace KPay\LaravelKPayment\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use KPay\LaravelKPayment\Exceptions\AuthenticationException;
use KPay\LaravelKPayment\Exceptions\PaymentException;

class KPayService
{
    protected Client $client;
    protected array $config;
    protected string $baseUrl;

    /**
     * Initialize the KPay service.
     */
    public function __construct()
    {
        $this->config = config('kpay');
        
        // Validate required configuration
        if (!$this->config['token'] || !$this->config['hash']) {
            throw new \RuntimeException(
                'KPay configuration incomplete. Please set KPAY_TOKEN and KPAY_HASH in your .env file'
            );
        }

        $this->baseUrl = $this->config['sandbox_mode'] 
            ? $this->config['sandbox_url'] 
            : $this->config['base_url'];

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->config['timeout'],
            'verify' => true, // Enable SSL verification
        ]);
    }

    /**
     * Generate a payment reference.
     *
     * @param string $price
     * @param string|null $description
     * @param string|null $expiry
     * @return array
     * @throws PaymentException
     */
    public function generateReference(
        string $price,
        ?string $description = null,
        ?string $expiry = null
    ): array {
        try {
            $payload = [
                'entity' => $this->config['entity'],
                'price' => $price,
            ];

            if ($description) {
                $payload['description'] = substr($description, 0, 30);
            }

            if ($expiry) {
                $payload['expiry'] = $expiry;
            } else {
                $payload['expiry'] = now()
                    ->addHours($this->config['reference_expiry_hours'])
                    ->format('Y-m-d H:i:s');
            }

            $response = $this->post('/sandbox/ref', $payload);

            if ($response['status'] === 200) {
                return $this->formatResponse($response);
            }

            throw new PaymentException('Failed to generate reference: ' . json_encode($response));
        } catch (GuzzleException $e) {
            $this->logError('generateReference', $e);
            throw new PaymentException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a payment reference.
     *
     * @param string $reference
     * @return bool
     * @throws PaymentException
     */
    public function cancelReference(string $reference): bool
    {
        try {
            $response = $this->post('/sandbox/request/cl', [
                'reference' => $reference,
            ]);

            if (in_array($response['status'] ?? 200, [200, 201])) {
                return true;
            }

            throw new PaymentException('Failed to cancel reference');
        } catch (GuzzleException $e) {
            $this->logError('cancelReference', $e);
            throw new PaymentException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if a reference has been paid.
     *
     * @param string $reference
     * @return array|null
     * @throws PaymentException
     */
    public function checkPayment(string $reference): ?array
    {
        try {
            $response = $this->post('/sandbox/request-paid', [
                'reference' => $reference,
            ]);

            if (in_array($response['status'] ?? 200, [200, 201])) {
                return $response;
            }

            return null;
        } catch (GuzzleException $e) {
            $this->logError('checkPayment', $e);
            throw new PaymentException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * List paid references.
     *
     * @return array
     * @throws PaymentException
     */
    public function listPaidReferences(): array
    {
        try {
            $response = $this->get('/sandbox/list');

            if (is_array($response)) {
                return $response;
            }

            return [];
        } catch (GuzzleException $e) {
            $this->logError('listPaidReferences', $e);
            throw new PaymentException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Simulate a payment (for testing).
     *
     * @param string $reference
     * @param string $amount
     * @return bool
     * @throws PaymentException
     */
    public function simulatePayment(string $reference, string $amount): bool
    {
        try {
            $response = $this->post('/sandbox/emulate', [
                'reference' => $reference,
                'amount' => $amount,
            ]);

            if (in_array($response['status'] ?? 200, [200, 201])) {
                return true;
            }

            throw new PaymentException('Failed to simulate payment');
        } catch (GuzzleException $e) {
            $this->logError('simulatePayment', $e);
            throw new PaymentException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws GuzzleException
     */
    protected function post(string $endpoint, array $data): array
    {
        $response = $this->client->post($endpoint, [
            'json' => $data,
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $endpoint
     * @return array|string
     * @throws GuzzleException
     */
    protected function get(string $endpoint)
    {
        $response = $this->client->get($endpoint, [
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get request headers.
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Sys-Marc-Zone' => $this->config['hash'],
            'Sys-Factory-Bag' => $this->config['factory_bag'],
            'Authorization' => 'Bearer ' . $this->config['token'],
        ];
    }

    /**
     * Format API response.
     *
     * @param array $response
     * @return array
     */
    protected function formatResponse(array $response): array
    {
        return [
            'success' => true,
            'reference' => $response['reference'] ?? null,
            'entity' => $response['entity'] ?? null,
            'price' => $response['price'] ?? null,
            'description' => $response['description'] ?? null,
            'status' => $response['status'] ?? 200,
            'expiry' => $response['expiry'] ?? null,
        ];
    }

    /**
     * Log error.
     *
     * @param string $method
     * @param \Exception $e
     */
    protected function logError(string $method, \Exception $e): void
    {
        if ($this->config['log_requests']) {
            Log::error("KPay Service Error [{$method}]: " . $e->getMessage());
        }
    }
}
