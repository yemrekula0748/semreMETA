<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use App\Services\MetaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstagramAccountController extends Controller
{
    public function __construct(private MetaApiService $metaApi) {}

    public function index()
    {
        $accounts = InstagramAccount::withCount(['conversations', 'users'])->get();
        return view('accounts.index', compact('accounts'));
    }

    public function connect()
    {
        $redirectUri = route('accounts.callback');
        $authUrl = $this->metaApi->getAuthUrl($redirectUri);
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('accounts.index')
                ->with('error', 'Instagram bağlantısı iptal edildi: ' . $request->get('error_description'));
        }

        $code = $request->get('code');
        $redirectUri = route('accounts.callback');

        $tokenData = $this->metaApi->getAccessToken($code, $redirectUri);

        if (!$tokenData || !isset($tokenData['access_token'])) {
            return redirect()->route('accounts.index')
                ->with('error', 'Access token alınamadı. Lütfen tekrar deneyin.');
        }

        // Long-lived token'a çevir
        $longLivedToken = $this->metaApi->getLongLivedToken($tokenData['access_token']);
        $accessToken = $longLivedToken ?? $tokenData['access_token'];

        // Facebook sayfalarını al
        $pages = $this->metaApi->getPages($accessToken);

        if (empty($pages)) {
            return redirect()->route('accounts.index')
                ->with('error', 'Hiç Facebook sayfası bulunamadı. Lütfen Instagram Business hesabınızı bir Facebook sayfasına bağlayın.');
        }

        $connected = 0;

        foreach ($pages as $page) {
            if (empty($page['instagram_business_account'])) {
                continue;
            }

            $igUserId = $page['instagram_business_account']['id'];
            $pageAccessToken = $page['access_token'];

            $igAccount = $this->metaApi->getInstagramAccount($igUserId, $pageAccessToken);

            if (!$igAccount) {
                continue;
            }

            InstagramAccount::updateOrCreate(
                ['instagram_user_id' => $igUserId],
                [
                    'name' => $igAccount['name'] ?? $page['name'],
                    'username' => $igAccount['username'] ?? null,
                    'page_id' => $page['id'],
                    'access_token' => $pageAccessToken,
                    'profile_picture_url' => $igAccount['profile_picture_url'] ?? null,
                    'is_active' => true,
                ]
            );

            // Webhook'a subscribe et
            $this->metaApi->subscribeToWebhook($page['id'], $pageAccessToken);

            $connected++;
        }

        if ($connected === 0) {
            return redirect()->route('accounts.index')
                ->with('error', 'Bağlanabilecek Instagram Business hesabı bulunamadı.');
        }

        return redirect()->route('accounts.index')
            ->with('success', "{$connected} Instagram hesabı başarıyla bağlandı.");
    }

    public function disconnect(InstagramAccount $account)
    {
        $account->update(['is_active' => false]);
        return redirect()->route('accounts.index')
            ->with('success', "'{$account->name}' hesabı devre dışı bırakıldı.");
    }

    public function activate(InstagramAccount $account)
    {
        $account->update(['is_active' => true]);
        return redirect()->route('accounts.index')
            ->with('success', "'{$account->name}' hesabı aktifleştirildi.");
    }

    public function destroy(InstagramAccount $account)
    {
        $account->delete();
        return redirect()->route('accounts.index')
            ->with('success', 'Hesap silindi.');
    }
}
