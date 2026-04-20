<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MetaApiService
{
    private Client $client;
    private string $apiVersion;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiVersion = config('services.meta.api_version', 'v19.0');
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}";
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
        ]);
    }

    /**
     * Instagram OAuth için yetkilendirme URL'ini oluşturur
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $appId = config('services.meta.app_id');
        $scopes = implode(',', [
            'instagram_manage_messages',
            'pages_manage_metadata',
            'pages_read_engagement',
        ]);

        return "https://www.facebook.com/dialog/oauth?" . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
        ]);
    }

    /**
     * Authorization code ile access token alır
     */
    public function getAccessToken(string $code, string $redirectUri): ?array
    {
        try {
            $response = $this->client->get('/oauth/access_token', [
                'query' => [
                    'client_id' => config('services.meta.app_id'),
                    'client_secret' => config('services.meta.app_secret'),
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Meta getAccessToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Short-lived token'i long-lived token'a çevirir
     */
    public function getLongLivedToken(string $shortLivedToken): ?string
    {
        try {
            $response = $this->client->get('/oauth/access_token', [
                'query' => [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => config('services.meta.app_id'),
                    'client_secret' => config('services.meta.app_secret'),
                    'fb_exchange_token' => $shortLivedToken,
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Meta getLongLivedToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kullanıcıya bağlı Facebook sayfalarını getirir
     */
    public function getPages(string $accessToken): array
    {
        try {
            $response = $this->client->get('/me/accounts', [
                'query' => [
                    'fields' => 'id,name,access_token,instagram_business_account',
                    'access_token' => $accessToken,
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Meta getPages error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Instagram Business Account bilgilerini getirir
     */
    public function getInstagramAccount(string $igUserId, string $accessToken): ?array
    {
        try {
            $response = $this->client->get("/{$igUserId}", [
                'query' => [
                    'fields' => 'id,name,username,profile_picture_url',
                    'access_token' => $accessToken,
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Meta getInstagramAccount error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Instagram konuşmalarını getirir (platform=instagram)
     */
    public function getConversations(string $igUserId, string $accessToken, string $after = null): array
    {
        try {
            $query = [
                'platform' => 'instagram',
                'fields' => 'id,participants,updated_time,messages{id,message,from,to,timestamp,attachments}',
                'access_token' => $accessToken,
                'limit' => 20,
            ];
            if ($after) {
                $query['after'] = $after;
            }

            $response = $this->client->get("/{$igUserId}/conversations", [
                'query' => $query,
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data ?? [];
        } catch (GuzzleException $e) {
            Log::error('Meta getConversations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Belirli bir konuşmanın mesajlarını getirir
     */
    public function getMessages(string $conversationId, string $accessToken, string $after = null): array
    {
        try {
            $query = [
                'fields' => 'id,message,from,to,timestamp,attachments{image_data,mime_type,name,file_url}',
                'access_token' => $accessToken,
                'limit' => 50,
            ];
            if ($after) {
                $query['after'] = $after;
            }

            $response = $this->client->get("/{$conversationId}/messages", [
                'query' => $query,
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Meta getMessages error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Metin mesajı gönderir
     */
    public function sendTextMessage(string $recipientIgId, string $text, string $accessToken): ?array
    {
        return $this->sendMessage($recipientIgId, ['text' => $text], $accessToken);
    }

    /**
     * Görsel mesajı gönderir (URL ile)
     */
    public function sendImageMessage(string $recipientIgId, string $imageUrl, string $accessToken): ?array
    {
        return $this->sendMessage($recipientIgId, [
            'attachment' => [
                'type' => 'image',
                'payload' => ['url' => $imageUrl, 'is_reusable' => true],
            ],
        ], $accessToken);
    }

    /**
     * Meta Messenger API'ye mesaj gönderir
     */
    private function sendMessage(string $recipientIgId, array $message, string $accessToken): ?array
    {
        try {
            $response = $this->client->post('/me/messages', [
                'query' => ['access_token' => $accessToken],
                'json' => [
                    'recipient' => ['id' => $recipientIgId],
                    'message' => $message,
                    'messaging_type' => 'RESPONSE',
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Meta sendMessage error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sayfayı webhook'a subscribe eder
     */
    public function subscribeToWebhook(string $pageId, string $pageAccessToken): bool
    {
        try {
            $response = $this->client->post("/{$pageId}/subscribed_apps", [
                'query' => ['access_token' => $pageAccessToken],
                'json' => [
                    'subscribed_fields' => ['messages', 'messaging_postbacks'],
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['success'] ?? false;
        } catch (GuzzleException $e) {
            Log::error('Meta subscribeToWebhook error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Attachment upload (dosya yükleme)
     */
    public function uploadAttachment(string $filePath, string $mimeType, string $accessToken): ?string
    {
        try {
            $response = $this->client->post('/me/message_attachments', [
                'query' => ['access_token' => $accessToken],
                'multipart' => [
                    [
                        'name' => 'message',
                        'contents' => json_encode([
                            'attachment' => [
                                'type' => 'image',
                                'payload' => ['is_reusable' => true],
                            ],
                        ]),
                    ],
                    [
                        'name' => 'filedata',
                        'contents' => fopen($filePath, 'r'),
                        'headers' => ['Content-Type' => $mimeType],
                    ],
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['attachment_id'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Meta uploadAttachment error: ' . $e->getMessage());
            return null;
        }
    }
}
