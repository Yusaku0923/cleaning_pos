<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(): Store
    {
        return Store::create([
            'name' => 'テスト店',
            'email' => 'store@example.com',
            'password' => Hash::make('secret-pass'),
            'address' => '大分県日田市',
            'phone_number' => '0973-00-0000',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertStatus(302)->assertRedirect('/login');
    }

    public function test_login_page_shows_legacy_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('login').'">', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('cdn', false)
            ->assertDontSee('担当者：', false);
    }

    public function test_login_succeeds_with_store_credentials(): void
    {
        $store = $this->makeStore();

        $this->post('/login', ['email' => 'store@example.com', 'password' => 'secret-pass'])
            ->assertStatus(302)
            ->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertAuthenticatedAs($store);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeStore();

        $this->from('/login')
            ->post('/login', ['email' => 'store@example.com', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_5_failures(): void
    {
        $this->makeStore();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'store@example.com', 'password' => 'wrong'])
                ->assertStatus(302);
        }

        // 6 回目は正しいパスワードでも止める
        $this->post('/login', ['email' => 'store@example.com', 'password' => 'secret-pass'])
            ->assertStatus(429);
        $this->assertGuest();

        // 別の email は止めない (email + IP 単位)
        $this->post('/login', ['email' => 'other@example.com', 'password' => 'wrong'])
            ->assertStatus(302);
    }

    public function test_logout(): void
    {
        $this->actingAs($this->makeStore());

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_device_token_logs_in_and_revoked_token_404(): void
    {
        $store = $this->makeStore();

        $this->assertSame(0, Artisan::call('device:issue', ['name' => 'レジ iPad']));
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('#/login/device/([A-Za-z0-9]{40,})#', $output);
        preg_match('#/login/device/([A-Za-z0-9]{40,})#', $output, $m);
        $plain = $m[1];

        $device = DeviceToken::sole();
        $this->assertSame('レジ iPad', $device->name);
        $this->assertNotSame($plain, $device->token_hash, '平文は保存しない');
        $this->assertNull($device->last_used_at);

        $this->get('/login/device/'.$plain)->assertRedirect('/');
        $this->assertAuthenticatedAs($store);
        $this->assertNotNull($device->fresh()->last_used_at);

        $this->post('/logout');
        $this->assertGuest();

        $this->get('/login/device/'.$plain.'x')->assertNotFound();
        $this->assertGuest();

        $this->assertSame(0, Artisan::call('device:revoke', ['id' => $device->id]));
        $this->assertNotNull($device->fresh()->revoked_at);

        $this->get('/login/device/'.$plain)->assertNotFound();
        $this->assertGuest();
    }

    public function test_api_accepts_session_cookie_without_bearer_token(): void
    {
        $store = $this->makeStore();

        $login = $this->post('/login', ['email' => 'store@example.com', 'password' => 'secret-pass']);
        $login->assertRedirect('/');
        $cookieName = config('session.cookie');
        $cookie = $login->getCookie($cookieName, false);
        $this->assertNotNull($cookie, 'ログインでセッション Cookie が出る');

        // メモリ上のログイン状態を捨て、Cookie だけで認証されることを確かめる
        $this->app['auth']->forgetGuards();
        $this->app['session.store']->flush();

        // Cookie なし → 401
        $this->withHeader('Referer', config('app.url').'/')
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Referer', config('app.url').'/')
            ->withCredentials() // JSON リクエストでも Cookie を送る (ブラウザの同一オリジン fetch と同じ)
            ->withUnencryptedCookie($cookieName, $cookie->getValue())
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson(['id' => $store->id, 'name' => 'テスト店'])
            ->assertJsonMissingPath('password');
    }
}
