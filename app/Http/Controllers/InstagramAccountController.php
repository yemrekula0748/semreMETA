<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use App\Services\MetaApiService;
use App\Services\InstagrapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstagramAccountController extends Controller
{
    public function __construct(
        private MetaApiService $metaApi,
        private InstagrapiService $instagrapi,
    ) {}

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

    public function igLogin(Request $request)
    {
        $data = $request->validate([
            'ig_username' => ['required', 'string', 'max:100'],
            'ig_password' => ['required', 'string'],
        ]);

        $result = $this->instagrapi->login($data['ig_username'], $data['ig_password']);

        if (!$result || isset($result['error'])) {
            $error = $result['error'] ?? 'Instagram girişi başarısız oldu.';

            // Challenge gerekiyor — kullanıcı adını session'a yaz, challenge modal göster
            if (str_starts_with($error, 'CHALLENGE_REQUIRED')) {
                session(['ig_challenge_username' => $data['ig_username']]);
                return redirect()->route('accounts.index')
                    ->with('challenge_required', $data['ig_username'])
                    ->with('error', '⚠️ Instagram güvenlik doğrulaması istedi. Instagram uygulamanızda "Şüpheli Giriş" / "Bu Benim" bildirimini onaylayın VEYA e-posta/SMS kodunu aşağıya girin.');
            }

            return redirect()->route('accounts.index')
                ->with('error', $error);
        }

        return $this->saveIgAccount($result, $data['ig_username']);
    }

    public function igChallengeResolve(Request $request)
    {
        $data = $request->validate([
            'ig_username' => ['required', 'string', 'max:100'],
            'challenge_code' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->instagrapi->challengeResolve($data['ig_username'], $data['challenge_code']);

        if (!$result || isset($result['error'])) {
            session(['ig_challenge_username' => $data['ig_username']]);
            return redirect()->route('accounts.index')
                ->with('challenge_required', $data['ig_username'])
                ->with('error', 'Kod doğrulaması başarısız: ' . ($result['error'] ?? 'Bilinmeyen hata'));
        }

        session()->forget('ig_challenge_username');
        return $this->saveIgAccount($result, $data['ig_username']);
    }

    public function igRetryAfterApproval(Request $request)
    {
        $data = $request->validate([
            'ig_username' => ['required', 'string', 'max:100'],
            'ig_password' => ['required', 'string'],
        ]);

        // Önceki session ayarlarıyla tekrar dene
        $result = $this->instagrapi->login($data['ig_username'], $data['ig_password']);

        if (!$result || isset($result['error'])) {
            $error = $result['error'] ?? 'Giriş başarısız.';
            if (str_starts_with($error, 'CHALLENGE_REQUIRED')) {
                session(['ig_challenge_username' => $data['ig_username']]);
                return redirect()->route('accounts.index')
                    ->with('challenge_required', $data['ig_username'])
                    ->with('error', 'Hâlâ doğrulama bekleniyor. Instagram uygulamasından "Bu Benim" seçeneğine bastınız mı?');
            }
            return redirect()->route('accounts.index')->with('error', $error);
        }

        session()->forget('ig_challenge_username');
        return $this->saveIgAccount($result, $data['ig_username']);
    }

    private function saveIgAccount(array $result, string $igUsername)
    {
        InstagramAccount::updateOrCreate(
            ['instagram_user_id' => $result['user_id']],
            [
                'name'                => $result['full_name'] ?? $igUsername,
                'username'            => $result['username'] ?? $igUsername,
                'page_id'             => null,
                'access_token'        => 'instagrapi_session',
                'profile_picture_url' => $result['profile_pic_url'] ?? null,
                'is_active'           => true,
                'service_type'        => 'instagrapi',
            ]
        );

        return redirect()->route('accounts.index')
            ->with('success', "@{$result['username']} hesabı başarıyla bağlandı.");
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
