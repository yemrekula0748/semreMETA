<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\InstagramAccount;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $accounts = InstagramAccount::where('is_active', true)->get();
        } else {
            $accounts = $user->instagramAccounts()->where('is_active', true)->get();
        }

        $accountIds = $accounts->pluck('id');

        $stats = [
            'total_accounts' => $accounts->count(),
            'total_conversations' => Conversation::whereIn('instagram_account_id', $accountIds)->count(),
            'unread_messages' => Conversation::whereIn('instagram_account_id', $accountIds)->sum('unread_count'),
            'today_messages' => Message::whereHas('conversation', function ($q) use ($accountIds) {
                $q->whereIn('instagram_account_id', $accountIds);
            })->whereDate('created_at', today())->count(),
        ];

        $recentConversations = Conversation::whereIn('instagram_account_id', $accountIds)
            ->with(['instagramAccount', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'recentConversations', 'accounts'));
    }
}
