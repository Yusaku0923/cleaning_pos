<?php

namespace Tests\Feature;

use App\Events\CustomerDisplayUpdated;
use App\Models\Customer;
use App\Models\Manager;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 顧客表示 (カスタマーディスプレイ)。旧は公開チャンネル `cleaning-pos` で顧客名が誰でも読めた。
 * v2 は店舗ごとの private channel `private-store.{storeId}.display`、表示端末は専用ログイン。
 */
class CustomerDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Store $other;

    private function makeStore(string $email): Store
    {
        return Store::create([
            'name' => '店', 'email' => $email, 'password' => 'x', 'address' => '住所', 'phone_number' => '000',
        ]);
    }

    private function makeCustomer(Store $store, string $name, bool $invoice = false): array
    {
        $t = ['created_at' => now(), 'updated_at' => now()];
        $mid = DB::table('managers')->insertGetId(['store_id' => $store->id, 'name' => '担当'] + $t);
        $cid = DB::table('customers')->insertGetId([
            'manager_id' => $mid, 'name' => $name, 'name_kana' => 'テスト', 'is_invoice' => $invoice,
        ] + $t);

        return [Manager::withoutGlobalScopes()->findOrFail($mid), Customer::withoutGlobalScopes()->findOrFail($cid)];
    }

    /** display:issue-token を実行し、表示された URL から平文トークンを取り出す */
    private function issueToken(?Store $store = null): string
    {
        $args = $store ? ['--store' => $store->id] : [];
        $this->assertSame(0, Artisan::call('display:issue-token', $args));
        $out = Artisan::output();
        $this->assertMatchesRegularExpression('#/display/login/([A-Za-z0-9]{40,})#', $out);
        preg_match('#/display/login/([A-Za-z0-9]{40,})#', $out, $m);

        return $m[1];
    }

    /** /broadcasting/auth の本物の署名を見るため、ダミー鍵の pusher ドライバに切り替えてチャンネルを登録し直す */
    private function usePusherWithDummyKeys(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => '1',
            'broadcasting.connections.pusher.options.cluster' => 'mt1',
        ]);
        $this->app->make(BroadcastManager::class)->purge();
        require base_path('routes/channels.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = $this->makeStore('a@example.com');
        $this->other = $this->makeStore('b@example.com');
    }

    public function test_broadcast_requires_auth(): void
    {
        $this->postJson('/api/customer-display', ['event' => 'finish'])->assertUnauthorized();
    }

    public function test_broadcast_sends_legacy_payload_on_store_private_channel(): void
    {
        Event::fake([CustomerDisplayUpdated::class]);
        $payload = ['event' => 'update', 'total' => 2, 'id' => 5, 'name' => 'Tシャツ', 'count' => 2, 'price' => 400, 'amount' => 400];

        $this->actingAs($this->store)->postJson('/api/customer-display', $payload)
            ->assertOk()->assertExactJson([]);

        Event::assertDispatched(CustomerDisplayUpdated::class, function (CustomerDisplayUpdated $e) use ($payload) {
            $channels = (array) $e->broadcastOn();

            return $e->broadcastAs() === 'customer-display'          // 旧と同じイベント名
                && $e->broadcastWith() === ['message' => $payload]    // 旧と同じ形 (req.message)
                && count($channels) === 1
                && $channels[0]->name === "private-store.{$this->store->id}.display";
        });
    }

    public function test_broadcast_succeeds_when_pusher_is_not_configured(): void
    {
        foreach (['null', 'log'] as $driver) {
            config(['broadcasting.default' => $driver]);
            $this->app->make(BroadcastManager::class)->purge();
            $this->actingAs($this->store)->postJson('/api/customer-display', ['event' => 'finish'])->assertOk();
        }

        // pusher なのに鍵が空 (XREA で .env 未設定) でも POS は止めない
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => null,
            'broadcasting.connections.pusher.secret' => null,
            'broadcasting.connections.pusher.app_id' => null,
        ]);
        $this->app->make(BroadcastManager::class)->purge();
        $this->actingAs($this->store)->postJson('/api/customer-display', ['event' => 'finish'])->assertOk();

        // 鍵だけあって secret 等が欠けている (ドライバの生成で例外) ときも 200
        config(['broadcasting.connections.pusher.key' => 'only-key']);
        $this->app->make(BroadcastManager::class)->purge();
        $this->actingAs($this->store)->postJson('/api/customer-display', ['event' => 'finish'])->assertOk();
    }

    public function test_display_route_requires_display_login(): void
    {
        // 未ログインでも POS の /login には飛ばさない。スタッフ向けの案内を全画面で出す
        $this->get('/customer_display')->assertStatus(401)
            ->assertSee(self::STAFF_MESSAGE)
            ->assertDontSee('<customer-display-component', false)
            ->assertDontSee('name="password"', false);
        // POS としてログインしているだけでは表示画面にはならない (表示端末のログインが要る)
        $this->actingAs($this->store)->get('/customer_display')->assertStatus(401)->assertSee(self::STAFF_MESSAGE);
    }

    private const STAFF_MESSAGE = '顧客表示の再ログインが必要です（スタッフ向け）';

    /** 表示端末でログインし、remember cookie (表示用ガードのもの) の名前と値を返す */
    private function displayLoginWithRemember(): array
    {
        $token = $this->issueToken($this->store);
        $res = $this->get('/display/login/'.$token)->assertRedirect('/customer_display')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $name = auth()->guard('display')->getRecallerName();
        $cookie = $res->getCookie($name, true, false);
        $this->assertNotNull($cookie, 'remember cookie が無い');
        // 少なくとも営業 1 日 (12 時間) より長く残る
        $this->assertGreaterThan(now()->addHours(12)->getTimestamp(), $cookie->getExpiresTime());
        $this->assertStringNotContainsString(auth()->guard('web')->getRecallerName(), $name);

        return [$name, $cookie->getValue()];
    }

    /** セッション切れ (SESSION_LIFETIME を過ぎて放置) を再現: 時間を進め、セッションとガードの状態を捨てる */
    private function expireSession(): void
    {
        $this->travel(config('session.lifetime') + 13 * 60)->minutes();
        $this->app['session']->driver()->flush();
        $this->app['auth']->forgetGuards();
        // auth:display ミドルウェアは既定ガードを display にする (本番はリクエストごとにアプリが新しいので残らない)
        $this->app['auth']->shouldUse('web');
        $this->flushSession();
    }

    public function test_display_survives_idle_session_expiry_via_remember_cookie(): void
    {
        $this->usePusherWithDummyKeys();
        [$name, $value] = $this->displayLoginWithRemember();

        $this->expireSession();
        $this->withCookie($name, $value)->get('/customer_display')->assertOk()
            ->assertSee('<customer-display-component', false);

        $this->expireSession();
        $this->withCookie($name, $value)
            ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-store.{$this->store->id}.display"])
            ->assertOk()->assertJsonStructure(['auth']);

        $this->expireSession();
        $this->withCookie($name, $value)->get('/display/ping')->assertNoContent();

        // 表示用の remember cookie では POS (web) には入れない
        $this->expireSession();
        $this->withCookie($name, $value)->get('/')->assertRedirect('/login');
        $this->withCookie($name, $value)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_keep_alive_ping(): void
    {
        $this->get('/display/ping')->assertUnauthorized();             // ブラウザの普通の GET でも 401 (リダイレクトしない)
        $this->actingAs($this->store)->get('/display/ping')->assertUnauthorized(); // POS のログインでは不可

        $this->displayLoginWithRemember();
        $this->get('/display/ping')->assertNoContent();
    }

    public function test_reissuing_token_invalidates_remembered_displays(): void
    {
        [$name, $value] = $this->displayLoginWithRemember();
        $this->issueToken($this->store);

        $this->expireSession();
        $this->withCookie($name, $value)->get('/customer_display')->assertStatus(401)->assertSee(self::STAFF_MESSAGE);
    }

    public function test_pos_web_login_and_logout_unchanged_and_do_not_break_display(): void
    {
        // (remember cookie にはパスワードのハッシュが入るので、パスワードは先に決めておく)
        $this->store->forceFill(['password' => Hash::make('pos-pass')])->save();
        [$name, $value] = $this->displayLoginWithRemember();

        // POS は今まで通り: ログインしても remember cookie は出さない、ログアウトできる
        $this->app['auth']->forgetGuards();
        $login = $this->post('/login', ['email' => 'a@example.com', 'password' => 'pos-pass']);
        $login->assertRedirect('/');
        $this->assertNull($login->getCookie(auth()->guard('web')->getRecallerName(), false));
        $this->post('/logout')->assertRedirect();
        $this->assertGuest('web');

        // POS のログアウトで表示端末の remember が切れない
        $this->expireSession();
        $this->withCookie($name, $value)->get('/customer_display')->assertOk();
    }

    public function test_display_token_login_works_and_wrong_token_404(): void
    {
        $this->get('/display/login/'.str_repeat('a', 48))->assertNotFound(); // 未発行

        $token = $this->issueToken($this->store);
        $stored = Setting::get('display_token');
        $this->assertIsArray($stored);
        $this->assertSame($this->store->id, $stored['store_id']);
        $this->assertNotSame($token, $stored['hash']);       // 平文は保存しない
        $this->assertSame(hash('sha256', $token), $stored['hash']);

        $this->get('/display/login/wrong-token')->assertNotFound();
        $this->get('/display/login/'.$token)->assertRedirect('/customer_display');
        $this->assertSame($this->store->id, auth()->guard('display')->id());
        $this->assertGuest('web');

        $this->get('/customer_display')->assertOk()
            ->assertSee('<customer-display-component :store-id="'.$this->store->id.'"></customer-display-component>', false)
            ->assertDontSee('担当者：', false); // 旧と同じくヘッダーは出さない

        // 再発行したら古いトークンは使えない
        $this->issueToken($this->store);
        auth()->guard('display')->logout();
        $this->get('/display/login/'.$token)->assertNotFound();
    }

    public function test_display_session_cannot_use_pos(): void
    {
        $token = $this->issueToken($this->store);
        $this->get('/display/login/'.$token)->assertRedirect('/customer_display');

        $this->get('/')->assertRedirect('/login');
        $this->get('/menu')->assertRedirect('/login');
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/customer-display', ['event' => 'finish'])->assertUnauthorized();
    }

    public function test_channel_authorizes_only_same_store(): void
    {
        $this->usePusherWithDummyKeys();
        $token = $this->issueToken($this->store);
        $this->get('/display/login/'.$token);

        $own = "private-store.{$this->store->id}.display";
        $this->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $own])
            ->assertOk()->assertJsonStructure(['auth']);

        $this->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-store.{$this->other->id}.display"])
            ->assertForbidden();
    }

    public function test_channel_auth_rejects_guest_and_pos_session(): void
    {
        $this->usePusherWithDummyKeys();
        $own = "private-store.{$this->store->id}.display";

        $res = $this->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $own]);
        $this->assertNotSame(200, $res->status());

        $res = $this->actingAs($this->store)->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $own]);
        $this->assertNotSame(200, $res->status());
    }

    public function test_customer_select_flashes_push_for_non_invoice_customer_only(): void
    {
        [$m, $c] = $this->makeCustomer($this->store, '山田');
        [$im, $ic] = $this->makeCustomer($this->store, '請求先', true);
        $this->actingAs($this->store);

        // GET では送らない。ホームを開いたときに画面から POST する (一度だけ)
        Event::fake([CustomerDisplayUpdated::class]);
        $this->get("/customer/select/{$c->id}?m={$m->id}")
            ->assertSessionHas('customer_display', ['event' => 'customer', 'name' => '山田']);
        Event::assertNotDispatched(CustomerDisplayUpdated::class);

        $this->get("/?m={$m->id}&c={$c->id}")->assertOk()
            ->assertSee('<meta name="customer-display-push"', false)
            ->assertSee(e(json_encode(['event' => 'customer', 'name' => '山田'], JSON_UNESCAPED_UNICODE)), false);
        // 再読み込みでは送らない
        $this->get("/?m={$m->id}&c={$c->id}")->assertOk()->assertDontSee('customer-display-push', false);

        $this->get("/customer/select/{$ic->id}?m={$im->id}")->assertSessionMissing('customer_display');
    }

    public function test_customer_store_pushes_for_non_invoice_customer_only(): void
    {
        [$m] = $this->makeCustomer($this->store, '既存');
        Event::fake([CustomerDisplayUpdated::class]);
        $this->actingAs($this->store);

        $this->post("/customer/store?m={$m->id}", ['name' => '新規', 'name_kana' => 'シンキ', 'is_invoice' => '0'])->assertRedirect();
        Event::assertDispatched(CustomerDisplayUpdated::class, fn ($e) => $e->broadcastWith() === ['message' => ['event' => 'customer', 'name' => '新規']]
            && $e->broadcastOn()[0]->name === "private-store.{$this->store->id}.display");

        Event::fake([CustomerDisplayUpdated::class]);
        $this->post("/customer/store?m={$m->id}", ['name' => '請求', 'name_kana' => 'セイキュウ', 'is_invoice' => '1'])->assertRedirect();
        Event::assertNotDispatched(CustomerDisplayUpdated::class);
    }
}
