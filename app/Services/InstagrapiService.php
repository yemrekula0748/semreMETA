<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

class InstagrapiService
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.instagrapi.url', 'http://127.0.0.1:8765'),
            'timeout' => 30,
        ]);
        $this->apiKey = config('services.instagrapi.api_key', 'semremeta_ig_2024');
    }

    private function headers(): array
    {
        return ['X-Api-Key' => $this->apiKey];
    }

    /**
     * Instagram'a kullanıcı adı/şifre ile giriş yap.
     * Başarılı olursa hesap bilgilerini döndürür.
     */
    public function login(string $username, string $password): ?array
    {
        try {
            $response = $this->client->post('/login', [
                'json' => ['username' => $username, 'password' => $password],
                'headers' => $this->headers(),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            return ['error' => $body['detail'] ?? 'Giriş yapılamadı.'];
        } catch (ConnectException $e) {
            return ['error' => 'Instagram servisi çalışmıyor. Sunucu yöneticinize başvurun.'];
        } catch (\Exception $e) {
            Log::error('InstagrapiService::login error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Hesabın DM konuşmalarını getir.
     */
    public function getThreads(string $igUsername, int $amount = 20): array
    {
        try {
            $response = $this->client->get("/threads/{$igUsername}", [
                'query' => ['amount' => $amount],
                'headers' => $this->headers(),
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                Log::warning("InstagrapiService: Session süresi doldu - {$igUsername}");
            }
            return [];
        } catch (\Exception $e) {
            Log::error("InstagrapiService::getThreads error ({$igUsername}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Bir konuşmanın mesajlarını getir.
     */
    public function getMessages(string $igUsername, string $threadId, int $amount = 30): array
    {
        try {
            $response = $this->client->get("/threads/{$igUsername}/{$threadId}/messages", [
                'query' => ['amount' => $amount],
                'headers' => $this->headers(),
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Exception $e) {
            Log::error("InstagrapiService::getMessages error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Thread'e mesaj gönder.
     */
    public function sendMessage(string $igUsername, string $threadId, string $text): ?array
    {
        try {
            $response = $this->client->post('/send', [
                'json' => [
                    'ig_username' => $igUsername,
                    'thread_id' => $threadId,
                    'text' => $text,
                ],
                'headers' => $this->headers(),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            Log::error("InstagrapiService::sendMessage error: " . ($body['detail'] ?? 'unknown'));
            return null;
        } catch (\Exception $e) {
            Log::error("InstagrapiService::sendMessage error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Challenge kodu iste (SMS/email).
     */
    public function challengeSend(string $username): array
    {
        try {
            $response = $this->client->post('/challenge/send', [
                'query' => ['username' => $username],
                'headers' => $this->headers(),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            return ['error' => $body['detail'] ?? 'Kod gönderilemedi.'];
        } catch (\Exception $e) {
            Log::error('InstagrapiService::challengeSend error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Challenge kodunu doğrula ve girişi tamamla.
     */
    public function challengeResolve(string $username, string $code): array
    {
        try {
            $response = $this->client->post('/challenge/resolve', [
                'json' => ['username' => $username, 'code' => $code],
                'headers' => $this->headers(),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            return ['error' => $body['detail'] ?? 'Kod doğrulaması başarısız.'];
        } catch (\Exception $e) {
            Log::error('InstagrapiService::challengeResolve error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Servisin çalışıp çalışmadığını kontrol et.
     */
    public function isAvailable(): bool
    {
        try {
            $this->client->get('/health', ['headers' => $this->headers(), 'timeout' => 3]);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
