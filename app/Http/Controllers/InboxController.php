<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\InstagramAccount;
use App\Models\Message;
use App\Services\MetaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InboxController extends Controller
{
    public function __construct(private MetaApiService $metaApi) {}

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

    public function show(Request $request, Conversation $conversation)
    {
        $accounts = $this->getAccessibleAccounts();
        $accountIds = $accounts->pluck('id');

        if (!$accountIds->contains($conversation->instagram_account_id)) {
            abort(403);
        }

        // Okunmamış mesajları okundu yap
        $conversation->messages()->where('is_read', false)->where('is_outgoing', false)->update(['is_read' => true]);
        $conversation->update(['unread_count' => 0]);

        $messages = $conversation->messages()->orderBy('sent_at')->get();

        $selectedAccount = $accounts->find($conversation->instagram_account_id);

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

        if ($request->hasFile('image')) {
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
