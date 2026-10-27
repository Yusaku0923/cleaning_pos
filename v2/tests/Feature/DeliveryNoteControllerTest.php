<?php

namespace Tests\Feature;

use App\Mail\DeliveryNoteMail;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryEmailLog;
use App\Models\DeliveryNote;
use App\Models\DeliveryProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Tests\Support\DeliveryFixtures;
use Tests\TestCase;

/** 旧 tests/Feature/DeliveryNoteControllerTest の移植 (納品書管理・PDF・メール)。旧の PDF は POST で呼んで通らなかったが、実際のルートは GET。 */
class DeliveryNoteControllerTest extends TestCase
{
    use DeliveryFixtures;
    use RefreshDatabase;

    public function test_requires_login(): void
    {
        $this->get('/delivery')->assertRedirect('/login');
        $this->get('/delivery/sp/entry')->assertRedirect('/login');
    }

    public function test_delivery_index_page_lists_customers(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        $this->makeDeliveryCustomer($this->makeStore('o@example.com'), '他店の会社');

        $this->actingAs($store)->get('/delivery/')
            ->assertOk()
            ->assertSee('納品書管理')
            ->assertSee('テスト商事')
            ->assertDontSee('他店の会社')
            ->assertSee('集計・PDF出力')
            ->assertSee("/delivery/notes/{$customer->id}", false)
            ->assertSee('商品管理')
            ->assertSee('送信履歴');
    }

    public function test_notes_page_loads_for_customer(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->get("/delivery/notes/{$customer->id}")
            ->assertOk()
            ->assertSee('<delivery-note-component', false)
            ->assertSee(substr(json_encode('総務部'), 1, -1), false);   // 部署は JSON (\uXXXX) で Vue に渡す (旧と同じ)
    }

    public function test_notes_page_of_other_store_is_404(): void
    {
        $other = $this->makeStore('o@example.com');
        [$theirs] = $this->makeDeliveryCustomer($other);

        $this->actingAs($this->makeStore())->get("/delivery/notes/{$theirs->id}")->assertNotFound();
        $this->actingAs($this->makeStore('x@example.com'))->get("/delivery/notes/{$theirs->id}/pdf?period_start=2026-03-01&period_end=2026-03-31")->assertNotFound();
        $this->assertSame(0, DeliveryNote::count());
    }

    public function test_sp_screens_load(): void
    {
        $store = $this->makeStore();
        $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->get('/delivery/sp/choice')->assertOk()->assertSee('納品書記帳')->assertSee('タブレット管理画面');
        $this->actingAs($store)->get('/delivery/sp/entry')->assertOk()->assertSee('<delivery-sp-entry-component', false)->assertSee(substr(json_encode('総務部'), 1, -1), false);
    }

    public function test_pdf_generates_and_creates_delivery_note_record(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store);
        $entry = $this->makeEntry($product, '2026-03-05', 3);
        $outside = $this->makeEntry($product, '2026-04-05', 3);

        $res = $this->actingAs($store)->get("/delivery/notes/{$customer->id}/pdf?period_start=2026-03-01&period_end=2026-03-31&cutoff_date=2026-03-25")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent());

        $note = DeliveryNote::where('delivery_customer_id', $customer->id)->sole();
        $this->assertSame(1, $note->note_number);
        $this->assertSame('2026-03-01', $note->period_start->toDateString());
        // 期間内の記帳だけが納品書に結びつく (確定)
        $this->assertSame($note->id, $entry->fresh()->delivery_note_id);
        $this->assertNull($outside->fresh()->delivery_note_id);
    }

    public function test_pdf_redownload_reuses_same_note_number(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        DeliveryNote::createWithNumber($customer->id, '2026-04-01', '2026-04-30');

        $this->actingAs($store)->get("/delivery/notes/{$customer->id}/pdf?period_start=2026-04-01&period_end=2026-04-30")->assertOk();

        $this->assertSame(1, DeliveryNote::where('delivery_customer_id', $customer->id)->where('period_start', '2026-04-01')->count());
    }

    public function test_pdf_does_not_move_entries_already_in_another_note(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store);
        $first = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-15');
        $old = $this->makeEntry($product, '2026-03-10', 1);
        $old->update(['delivery_note_id' => $first->id]);
        $new = $this->makeEntry($product, '2026-03-20', 1);

        $this->actingAs($store)->get("/delivery/notes/{$customer->id}/pdf?period_start=2026-03-01&period_end=2026-03-31")->assertOk();

        $second = DeliveryNote::where('note_number', 2)->sole();
        $this->assertSame($first->id, $old->fresh()->delivery_note_id);
        $this->assertSame($second->id, $new->fresh()->delivery_note_id);
    }

    public function test_pdf_validates_period(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->get("/delivery/notes/{$customer->id}/pdf")->assertSessionHasErrors(['period_start', 'period_end']);
        $this->actingAs($store)->get("/delivery/notes/{$customer->id}/pdf?period_start=2026-03-31&period_end=2026-03-01")->assertSessionHasErrors('period_end');
        $this->assertSame(0, DeliveryNote::count());
    }

    public function test_pdf_view_has_floor_tax_and_store_header(): void
    {
        $store = $this->makeStore();
        [$customer, $dept, $product] = $this->makeDeliveryCustomer($store, price: 155);
        $this->makeEntry($product, '2026-03-03', 3);   // 465 → 税 46
        $this->actingAs($store);

        $html = view('delivery.pdf', [
            'customer' => $customer, 'note' => DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31'),
            'entries' => [['name' => '総務部', 'subtotal' => 465, 'products' => [['name' => 'ワイシャツ', 'quantity' => 3, 'unit_price' => 155, 'amount' => 465, 'tax_rate' => 0.1]]]],
            'taxGroups' => [['rate' => 0.1, 'subtotal_excl' => 465, 'tax' => 46, 'subtotal_incl' => 511]],
            'periodStart' => '2026-03-01', 'periodEnd' => '2026-03-31', 'cutoffDate' => '2026-03-25',
        ])->render();

        $this->assertStringContainsString('＊ ＊ 請求書 ＊ ＊', $html);
        $this->assertStringContainsString('テスト商事', $html);
        $this->assertStringContainsString('あさひ屋', $html);
        $this->assertStringContainsString('¥511', $html);
    }

    public function test_note_numbers_are_sequential_per_customer(): void
    {
        $store = $this->makeStore();
        [$a] = $this->makeDeliveryCustomer($store, 'A社');
        [$b] = $this->makeDeliveryCustomer($store, 'B社');

        $this->assertSame(1, DeliveryNote::createWithNumber($a->id, '2026-01-01', '2026-01-31')->note_number);
        $this->assertSame(2, DeliveryNote::createWithNumber($a->id, '2026-02-01', '2026-02-28')->note_number);
        $this->assertSame(1, DeliveryNote::createWithNumber($b->id, '2026-01-01', '2026-01-31')->note_number);
    }

    public function test_email_form_shows_default_body(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');

        $this->actingAs($store)->get("/delivery/notes/{$customer->id}/email?note_id={$note->id}")
            ->assertOk()
            ->assertSee('メール送信確認')
            ->assertSee('client@example.com')
            ->assertSee('3月1日〜3月31日分の納品書を添付いたします。')
            ->assertSee('送信する');
        $this->actingAs($store)->get("/delivery/notes/{$customer->id}/email")->assertSessionHasErrors('note_id');
    }

    public function test_send_email_sends_mail_with_pdf_and_logs(): void
    {
        Mail::fake();
        Date::setTestNow('2026-04-02 09:30:00');
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store, price: 155);
        $this->makeEntry($product, '2026-03-03', 3);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');

        $this->actingAs($store)->post("/delivery/notes/{$customer->id}/email", ['note_id' => $note->id, 'body' => '本文です'])
            ->assertRedirect(route('delivery.email_logs'))
            ->assertSessionHas('message', '送信しました');

        Mail::assertSent(DeliveryNoteMail::class, function (DeliveryNoteMail $m) use ($note) {
            return $m->hasTo('client@example.com')
                && $m->body === '本文です'
                && $m->taxGroups[0]['tax'] === 46
                && $m->hasSubject("納品書 No.{$note->note_number}（3月1日〜3月31日分）");
        });
        $this->assertDatabaseHas('delivery_email_logs', [
            'delivery_note_id' => $note->id, 'delivery_customer_id' => $customer->id, 'sent_to' => 'client@example.com',
            'status' => 'success', 'period_start' => '2026-03-01', 'period_end' => '2026-03-31',
        ]);
    }

    public function test_mailable_attaches_pdf_and_renders_body(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');
        $this->actingAs($store);

        $mail = new DeliveryNoteMail($customer, $note, [], [], "一行目\n<b>二行目</b>");

        $mail->assertSeeInHtml('一行目<br />', false);
        $mail->assertSeeInHtml('&lt;b&gt;二行目&lt;/b&gt;', false);

        // 実際に組み立てて (array トランスポート)、PDF が添付されているところまで見る
        Mail::mailer('array')->to('client@example.com')->send(new DeliveryNoteMail($customer, $note, [], [], 'x'));
        $sent = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $attachments = $sent->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame("納品書_テスト商事_No{$note->note_number}.pdf", $attachments[0]->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'));
        $this->assertSame('application/pdf', $attachments[0]->getMediaType().'/'.$attachments[0]->getMediaSubtype());
        $this->assertStringStartsWith('%PDF', $attachments[0]->getBody());
    }

    public function test_send_email_failure_is_logged_as_failed(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');

        $this->actingAs($store)->post("/delivery/notes/{$customer->id}/email", ['note_id' => $note->id, 'body' => 'x'])
            ->assertRedirect(route('delivery.email_logs'))
            ->assertSessionHas('message', '送信に失敗しました');

        $this->assertSame('failed', DeliveryEmailLog::sole()->status);
    }

    public function test_send_email_requires_body_and_own_note(): void
    {
        Mail::fake();
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        [$otherCustomer] = $this->makeDeliveryCustomer($this->makeStore('o@example.com'), '他店');
        $theirNote = DeliveryNote::withoutGlobalScopes()->create(['delivery_customer_id' => $otherCustomer->id, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31', 'note_number' => 1]);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');
        $other = DeliveryNote::createWithNumber($this->makeDeliveryCustomer($store, '別の会社')[0]->id, '2026-03-01', '2026-03-31');

        $this->actingAs($store)->post("/delivery/notes/{$customer->id}/email", ['note_id' => $note->id])->assertSessionHasErrors('body');
        // 他店の納品書・別の取引先の納品書は選べない
        $this->actingAs($store)->post("/delivery/notes/{$customer->id}/email", ['note_id' => $theirNote->id, 'body' => 'x'])->assertSessionHasErrors('note_id');
        $this->actingAs($store)->post("/delivery/notes/{$customer->id}/email", ['note_id' => $other->id, 'body' => 'x'])->assertSessionHasErrors('note_id');
        Mail::assertNothingSent();
        $this->assertSame(0, DeliveryEmailLog::count());
    }

    public function test_send_email_without_address_is_refused(): void
    {
        Mail::fake();
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store, email: null);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');

        $this->actingAs($store)->post("/delivery/notes/{$customer->id}/email", ['note_id' => $note->id, 'body' => 'x'])->assertStatus(422);
        Mail::assertNothingSent();
    }

    public function test_email_logs_page_lists_own_store_only(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');
        DeliveryEmailLog::create([
            'delivery_note_id' => $note->id, 'delivery_customer_id' => $customer->id, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31',
            'sent_to' => 'client@example.com', 'sent_at' => '2026-04-02 09:30:00', 'status' => 'failed',
        ]);
        [$theirs] = $this->makeDeliveryCustomer($this->makeStore('o@example.com'), '他店の会社', 'theirs@example.com');
        $theirNote = DeliveryNote::withoutGlobalScopes()->create(['delivery_customer_id' => $theirs->id, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31', 'note_number' => 1]);
        DeliveryEmailLog::withoutGlobalScopes()->create([
            'delivery_note_id' => $theirNote->id, 'delivery_customer_id' => $theirs->id, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31',
            'sent_to' => 'theirs@example.com', 'sent_at' => '2026-04-02 09:30:00', 'status' => 'success',
        ]);

        $this->actingAs($store)->get('/delivery/email-logs')
            ->assertOk()
            ->assertSee('メール送信履歴')
            ->assertSee('2026/04/02 09:30')
            ->assertSee('client@example.com')
            ->assertSee('No.1')
            ->assertSee('❌ 失敗')
            ->assertDontSee('theirs@example.com');
    }

    public function test_floor_tax_follows_config(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store, price: 155);
        $this->makeEntry($product, '2026-03-03', 3);   // 465 * 10% = 46.5
        $url = "/api/delivery/preview?customer_id={$customer->id}&period_start=2026-03-01&period_end=2026-03-31";

        $this->actingAs($store)->getJson($url)->assertJsonPath('tax_groups.0.tax', 46);
        config(['pos.tax_rounding.delivery' => 'round']);
        $this->actingAs($store)->getJson($url)->assertJsonPath('tax_groups.0.tax', 47);
    }

    public function test_aggregation_skips_zero_quantity_and_empty_departments(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store);
        DeliveryProduct::create(['delivery_department_id' => $product->delivery_department_id, 'name' => '未使用', 'unit_price' => 10, 'tax_rate' => '0.10', 'sort_order' => 2]);
        $this->makeEntry($product, '2026-03-03', 0);

        $this->actingAs($store)->getJson("/api/delivery/preview?customer_id={$customer->id}&period_start=2026-03-01&period_end=2026-03-31")
            ->assertOk()->assertJsonPath('entries', [])->assertJsonPath('tax_groups', []);
        $this->assertSame(1, DeliveryDailyEntry::count());
    }
}
