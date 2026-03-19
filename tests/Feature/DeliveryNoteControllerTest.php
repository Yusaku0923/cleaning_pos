<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryNote;

class DeliveryNoteControllerTest extends TestCase
{
    private function actingAsStore()
    {
        $store = \App\Models\Store::first();
        if (!$store) $this->markTestSkipped('No Store record in DB');
        return $this->actingAs($store);
    }

    public function test_delivery_index_page_loads()
    {
        $this->actingAsStore()
            ->get('/delivery/')
            ->assertOk();
    }

    public function test_notes_page_loads_for_customer()
    {
        $customer = DeliveryCustomer::first();
        if (!$customer) $this->markTestSkipped('No customers in DB');

        $this->actingAsStore()
            ->get("/delivery/notes/{$customer->id}")
            ->assertOk();
    }

    public function test_pdf_generates_and_creates_delivery_note_record()
    {
        $customer = DeliveryCustomer::first();
        if (!$customer) $this->markTestSkipped('No customers in DB');

        $this->actingAsStore()
            ->post("/delivery/notes/{$customer->id}/pdf", [
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('delivery_notes', [
            'delivery_customer_id' => $customer->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
        ]);

        // Cleanup
        DeliveryNote::where('delivery_customer_id', $customer->id)
            ->where('period_start', '2026-03-01')->delete();
    }

    public function test_pdf_redownload_reuses_same_note_number()
    {
        $customer = DeliveryCustomer::first();
        if (!$customer) $this->markTestSkipped('No customers in DB');

        DeliveryNote::createWithNumber($customer->id, '2026-04-01', '2026-04-30');

        $this->actingAsStore()
            ->post("/delivery/notes/{$customer->id}/pdf", [
                'period_start' => '2026-04-01',
                'period_end' => '2026-04-30',
            ])
            ->assertOk();

        $this->assertEquals(
            1,
            DeliveryNote::where('delivery_customer_id', $customer->id)
                ->where('period_start', '2026-04-01')->count()
        );

        // Cleanup
        DeliveryNote::where('delivery_customer_id', $customer->id)
            ->where('period_start', '2026-04-01')->delete();
    }
}
