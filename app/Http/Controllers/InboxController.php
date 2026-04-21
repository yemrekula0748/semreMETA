<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\InstagramAccount;
use App\Models\Message;
use App\Services\MetaApiService;
use App\Services\InstagrapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InboxController extends Controller
{
    public function __construct(
        private MetaApiService $metaApi,
        private InstagrapiService $instagrapi,
    ) {}

    private function getAccessibleAccounts()
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return InstagramAccount::where('is_active', true)->get();
        }
        return $user->instagramAccounts()->where('is_active', true)->get();
    }

    public function index(Request $request)
    {
        $accounts = $this->getAccessibleAccounts();

        if ($accounts->isEmpty()) {
            return view('inbox.index', [
                'accounts' => $accounts,
                'conversations' => collect(),
                'selectedAccount' => null,
                'selectedConversation' => null,
            ]);
        }

        $accountId = $request->get('account', $accounts->first()->id);
        $selectedAccount = $accounts->find($accountId) ?? $accounts->first();

        // instagrapi hesapları için thread'leri DB'ye senkronize et
        if ($selectedAccount->service_type === 'instagrapi') {
            $this->syncInstagrapiThreads($selectedAccount);
        }

        $conversations = Conversation::where('instagram_account_id', $selectedAccount->id)
            ->orderByDesc('last_message_at')
            ->get();

        return view('inbox.index', [
            'accounts' => $accounts,
            'conversations' => $conversations,
            'selectedAccount' => $selectedAccount,
            'selectedConversation' => null,
        ]);
    }

    /**
     * instagrapi servisinden thread'leri çekip Conversations tablosuna yazar.
     */
    private function syncInstagrapiThreads(InstagramAccount $account): void
    {
        $threads = $this->instagrapi->getThreads($account->username);

        foreach ($threads as $thread) {
            $otherUser = $thread['users'][0] ?? null;
            if (!$otherUser) continue;

            $lastAt = $thread['last_activity']
                ? \Carbon\Carbon::parse($thread['last_activity'])
                : now();

            Conversation::updateOrCreate(
                [
                    'instagram_account_id' => $account->id,
                    'ig_conversation_id'   => $thread['id'],
                ],
                [
                    'participant_ig_id'      => $otherUser['pk'],
                    'participant_username'   => $otherUser['username'],
                    'participant_name'       => $otherUser['full_name'] ?? $otherUser['username'],
                    'participant_pic'        => $otherUser['profile_pic_url'] ?? null,
                    'last_message_at'        => $lastAt,
                    'unread_count'           => $thread['unread_count'] ?? 0,
                ]
            );
        }
    }

    public function show(Request $request, Conversation $conversation)
    {
        $accounts = $this->getAccessibleAccounts();
        $accountIds = $accounts->pluck('id');

        if (!$accountIds->contains($conversation->instagram_account_id)) {
            abort(403);
        }

        $selectedAccount = $accounts->find($conversation->instagram_account_id);

        // instagrapi hesapları için mesajları DB'ye senkronize et
        if ($selectedAccount->service_type === 'instagrapi') {
            $this->syncInstagrapiMessages($conversation, $selectedAccount);
        }

        // Okunmamış mesajları okundu yap
        $conversation->messages()->where('is_read', false)->where('is_outgoing', false)->update(['is_read' => true]);
        $conversation->update(['unread_count' => 0]);

        $messages = $conversation->messages()->orderBy('sent_at')->get();

        $conversations = Conversation::where('instagram_account_id', $selectedAccount->id)
            ->orderByDesc('last_message_at')
            ->get();

        return view('inbox.index', [
            'accounts' => $accounts,
            'conversations' => $conversations,
            'selectedAccount' => $selectedAccount,
            'selectedConversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * instagrapi servisinden mesajları çekip Messages tablosuna yazar.
     */
    private function syncInstagrapiMessages(Conversation $conversation, InstagramAccount $account): void
    {
        $messages = $this->instagrapi->getMessages($account->username, $conversation->ig_conversation_id);

        foreach ($messages as $msg) {
            $ts = $msg['timestamp'] ? \Carbon\Carbon::parse($msg['timestamp']) : now();

            Message::updateOrCreate(
                ['meta_message_id' => $msg['id']],
                [
                    'conversation_id' => $conversation->id,
                    'from_ig_id'      => $msg['is_outgoing'] ? $account->instagram_user_id : $conversation->participant_ig_id,
                    'to_ig_id'        => $msg['is_outgoing'] ? $conversation->participant_ig_id : $account->instagram_user_id,
                    'message_text'    => $msg['text'] ?: null,
                    'message_type'    => in_array($msg['item_type'], ['text', 'image', 'video']) ? $msg['item_type'] : 'text',
                    'media_url'       => $msg['media_url'] ?? null,
                    'is_outgoing'     => $msg['is_outgoing'],
                    'is_read'         => $msg['is_outgoing'],
                    'sent_at'         => $ts,
                ]
            );
        }
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $accounts = $this->getAccessibleAccounts();
        if (!$accounts->pluck('id')->contains($conversation->instagram_account_id)) {
            abort(403);
        }

        $request->validate([
            'message' => ['required_without:image', 'nullable', 'string', 'max:2000'],
            'image' => ['required_without:message', 'nullable', 'file', 'image', 'max:10240'],
        ]);

        $account = $conversation->instagramAccount;
        $recipientId = $conversation->participant_ig_id;

        // instagrapi hesabı ise Python servisi üzerinden gönder
        if ($account->service_type === 'instagrapi') {
            // Meta API hesabı (resmi)
        if ($request->hasFile('image')) {
                return back()->with('error', 'instagrapi ile resim gönderme henüz desteklenmiyor. Lütfen metin mesajı gönderin.');
            }

            $text = $request->input('message');
            $result = $this->instagrapi->sendMessage($account->username, $conversation->ig_conversation_id, $text);

            if (!$result) {
                return back()->with('error', 'Mesaj gönderilemedi.');
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'meta_message_id' => $result['message_id'] ?? ('ig_' . uniqid()),
                'from_ig_id'      => $account->instagram_user_id,
                'to_ig_id'        => $recipientId,
                'message_text'    => $text,
                'message_type'    => 'text',
                'media_url'       => null,
                'is_outgoing'     => true,
                'is_read'         => true,
                'sent_at'         => now(),
            ]);

            $conversation->update(['last_message_at' => now()]);
            return redirect()->route('inbox.show', $conversation);
        }
            $file = $request->file('image');
            $path = $file->store('uploads/messages', 'public');
            $publicUrl = Storage::disk('public')->url($path);

            $result = $this->metaApi->sendImageMessage($recipientId, $publicUrl, $account->access_token);

            if (!$result) {
                return back()->with('error', 'Resim gönderilemedi. Lütfen tekrar deneyin.');
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'meta_message_id' => $result['message_id'] ?? null,
                'from_ig_id' => $account->instagram_user_id,
                'to_ig_id' => $recipientId,
                'message_text' => null,
                'message_type' => 'image',
                'media_url' => $publicUrl,
                'is_outgoing' => true,
                'is_read' => true,
                'sent_at' => now(),
            ]);
        } else {
            $text = $request->input('message');
            $result = $this->metaApi->sendTextMessage($recipientId, $text, $account->access_token);

            if (!$result) {
                return back()->with('error', 'Mesaj gönderilemedi. Lütfen tekrar deneyin.');
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'meta_message_id' => $result['message_id'] ?? null,
                'from_ig_id' => $account->instagram_user_id,
                'to_ig_id' => $recipientId,
                'message_text' => $text,
                'message_type' => 'text',
                'media_url' => null,
                'is_outgoing' => true,
                'is_read' => true,
                'sent_at' => now(),
            ]);
        }

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('inbox.show', $conversation);
    }

    // AJAX polling endpoint - yeni mesajları getirir
    public function pollMessages(Request $request, Conversation $conversation)
    {
        $accounts = $this->getAccessibleAccounts();
        if (!$accounts->pluck('id')->contains($conversation->instagram_account_id)) {
            abort(403);
        }

        $selectedAccount = $accounts->find($conversation->instagram_account_id);

        // instagrapi hesapları için polling sırasında da senkronize et
        if ($selectedAccount->service_type === 'instagrapi') {
            $this->syncInstagrapiMessages($conversation, $selectedAccount);
        }

        $since = $request->get('since');
        $query = $conversation->messages()->orderBy('sent_at');

        if ($since) {
            $query->where('sent_at', '>', $since);
        }

        $messages = $query->get()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'message_text' => $msg->message_text,
                'message_type' => $msg->message_type,
                'media_url' => $msg->media_url,
                'is_outgoing' => $msg->is_outgoing,
                'sent_at' => $msg->sent_at->toIso8601String(),
                'sent_at_human' => $msg->sent_at->format('H:i'),
            ];
        });

        // Okunmamış olarak işaretle
        $conversation->messages()->where('is_read', false)->where('is_outgoing', false)->update(['is_read' => true]);
        $conversation->update(['unread_count' => 0]);

        return response()->json(['messages' => $messages]);
    }

    // AJAX polling endpoint - konuşma listesini günceller
    public function pollConversations(Request $request)
    {
        $accounts = $this->getAccessibleAccounts();
        $accountId = $request->get('account');
        $selectedAccount = $accounts->find($accountId);

        if (!$selectedAccount) {
            return response()->json(['conversations' => []]);
        }

        $conversations = Conversation::where('instagram_account_id', $selectedAccount->id)
            ->with('latestMessage')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'display_name' => $conv->display_name,
                    'participant_profile_pic' => $conv->participant_profile_pic,
                    'unread_count' => $conv->unread_count,
                    'last_message_at' => $conv->last_message_at?->diffForHumans(),
                    'last_message' => $conv->latestMessage?->message_text ?? ($conv->latestMessage?->message_type === 'image' ? '📷 Resim' : ''),
                ];
            });

        return response()->json(['conversations' => $conversations]);
    }
}
