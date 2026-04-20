<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\InstagramAccount;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * GET - Meta webhook doğrulama
     */
    public function verify(Request $request)
    {
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.meta.webhook_verify_token')) {
            return response($challenge, 200);
        }

        return response('Unauthorized', 403);
    }

    /**
     * POST - Gelen mesajları işle
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook alındı', $payload);

        if ($payload['object'] !== 'instagram') {
            return response()->json(['status' => 'ignored']);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $this->processMessaging($messaging);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function processMessaging(array $messaging): void
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $recipientId = $messaging['recipient']['id'] ?? null;

        if (!$senderId || !$recipientId) return;

        // Kendi gönderdiğimiz mesajları atla
        $account = InstagramAccount::where('instagram_user_id', $recipientId)->first();

        if (!$account) {
            // Belki biz gonderdik (sender = biziz)
            $account = InstagramAccount::where('instagram_user_id', $senderId)->first();
            if (!$account) return;
        }

        $isOutgoing = ($senderId === $account->instagram_user_id);
        $participantId = $isOutgoing ? $recipientId : $senderId;

        // Konuşmayı bul veya oluştur
        $conversation = Conversation::firstOrCreate(
            [
                'instagram_account_id' => $account->id,
                'participant_ig_id' => $participantId,
            ],
            [
                'participant_username' => null,
                'participant_name' => null,
                'last_message_at' => now(),
            ]
        );

        // Mesaj içeriği
        $messageData = $messaging['message'] ?? null;
        if (!$messageData) return;

        $metaMessageId = $messageData['mid'] ?? null;

        // Duplicate kontrolü
        if ($metaMessageId && Message::where('meta_message_id', $metaMessageId)->exists()) {
            return;
        }

        $messageType = 'text';
        $messageText = $messageData['text'] ?? null;
        $mediaUrl = null;

        if (isset($messageData['attachments'])) {
            foreach ($messageData['attachments'] as $attachment) {
                $type = $attachment['type'] ?? '';
                if (in_array($type, ['image', 'video', 'audio', 'file'])) {
                    $messageType = $type;
                    $mediaUrl = $attachment['payload']['url'] ?? null;
                    break;
                }
            }
        }

        $sentAt = isset($messaging['timestamp'])
            ? \Carbon\Carbon::createFromTimestampMs($messaging['timestamp'])
            : now();

        Message::create([
            'conversation_id' => $conversation->id,
            'meta_message_id' => $metaMessageId,
            'from_ig_id' => $senderId,
            'to_ig_id' => $recipientId,
            'message_text' => $messageText,
            'message_type' => $messageType,
            'media_url' => $mediaUrl,
            'is_outgoing' => $isOutgoing,
            'is_read' => $isOutgoing,
            'sent_at' => $sentAt,
        ]);

        // Konuşmayı güncelle
        $updateData = ['last_message_at' => $sentAt];
        if (!$isOutgoing) {
            $updateData['unread_count'] = $conversation->unread_count + 1;
        }
        $conversation->update($updateData);
    }
}
