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

    public function manualConnect(Request $request)
    {
        $data = $request->validate([
            'access_token' => ['required', 'string'],
            'account_name' => ['nullable', 'string', 'max:100'],
        ]);

        $accessToken = $data['access_token'];

        // Önce long-lived token'a çevir
        $longLivedToken = $this->metaApi->getLongLivedToken($accessToken);
        $finalToken = $longLivedToken ?? $accessToken;

        // Facebook sayfalarını dene
        $pages = $this->metaApi->getPages($finalToken);

        $connected = 0;

        if (!empty($pages)) {
            foreach ($pages as $page) {
                if (empty($page['instagram_business_account'])) continue;

                $igUserId = $page['instagram_business_account']['id'];
                $pageToken = $page['access_token'] ?? $finalToken;
                $igAccount = $this->metaApi->getInstagramAccount($igUserId, $pageToken);

                InstagramAccount::updateOrCreate(
                    ['instagram_user_id' => $igUserId],
                    [
                        'name' => $igAccount['name'] ?? $page['name'] ?? $data['account_name'] ?? 'Instagram Hesabı',
                        'username' => $igAccount['username'] ?? null,
                        'page_id' => $page['id'],
                        'access_token' => $pageToken,
                        'profile_picture_url' => $igAccount['profile_picture_url'] ?? null,
                        'is_active' => true,
                    ]
                );
                $this->metaApi->subscribeToWebhook($page['id'], $pageToken);
                $connected++;
            }
        }

        // Sayfa bulunamazsa token'ı doğrudan Instagram User token olarak dene
        if ($connected === 0) {
            $meData = $this->metaApi->getMeAsInstagramUser($finalToken);

            if ($meData && isset($meData['id'])) {
                InstagramAccount::updateOrCreate(
                    ['instagram_user_id' => $meData['id']],
                    [
                        'name' => $meData['name'] ?? $data['account_name'] ?? 'Instagram Hesabı',
                        'username' => $meData['username'] ?? null,
                        'page_id' => null,
                        'access_token' => $finalToken,
                        'profile_picture_url' => $meData['profile_picture_url'] ?? null,
                        'is_active' => true,
                    ]
                );
                $connected++;
            }
        }

        if ($connected === 0) {
            return redirect()->route('accounts.index')
                ->with('error', 'Token geçersiz veya bu token ile Instagram Business hesabına erişilemiyor. Lütfen Graph API Explorer\'dan instagram_manage_messages iznini seçerek yeni token alın.');
        }

        return redirect()->route('accounts.index')
            ->with('success', "{$connected} Instagram hesabı başarıyla eklendi.");
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
