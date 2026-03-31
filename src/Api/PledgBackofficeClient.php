<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * OAuth + refund API (aligné sur le flux Odoo payment_pledg : /api/token + credit_by_transfer).
 */
final class PledgBackofficeClient
{
    public function __construct(
        private string $backBaseUrl,
        private ClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function getAccessToken(string $apiKey, string $apiSecret, ?string $baseUrlOverride = null): string
    {
        $base = $baseUrlOverride ?? $this->backBaseUrl;
        $url = rtrim($base, '/') . '/api/token';
        $basic = base64_encode($apiKey . ':' . $apiSecret);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $basic,
                ],
                'body' => 'grant_type=client_credentials',
                'timeout' => 30,
            ]);
            $data = json_decode((string) $response->getBody(), true);
            $token = \is_array($data) ? ($data['access_token'] ?? null) : null;
            if (!\is_string($token) || $token === '') {
                throw new \RuntimeException('Réponse token Pledg invalide (access_token manquant).');
            }

            return $token;
        } catch (GuzzleException $e) {
            $this->logger->error('Pledg token error: ' . $e->getMessage());

            throw new \RuntimeException('Impossible d’obtenir le token API Pledg.', 0, $e);
        }
    }

    /**
     * @return array<mixed>
     */
    public function creditByTransfer(string $purchaseUid, int $amountCents, string $accessToken, ?string $baseUrlOverride = null): array
    {
        $base = $baseUrlOverride ?? $this->backBaseUrl;
        $endpoint = sprintf('/api/purchases/%s/credit_by_transfer', rawurlencode($purchaseUid));
        $url = rtrim($base, '/') . $endpoint;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => ['amount_cents' => $amountCents],
                'timeout' => 60,
            ]);
            $body = (string) $response->getBody();

            return $body !== '' ? (json_decode($body, true) ?: []) : [];
        } catch (GuzzleException $e) {
            $this->logger->error('Pledg credit_by_transfer error: ' . $e->getMessage());

            throw new \RuntimeException('Échec du remboursement Sofinco (API Pledg).', 0, $e);
        }
    }
}
