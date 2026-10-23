<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertNull(Setting::get('printer_ip'));
        $this->assertSame('0.0.0.0', Setting::get('printer_ip', '0.0.0.0'));
    }

    public function test_set_then_get_keeps_value_and_type(): void
    {
        Setting::set('printer_ip', '192.168.0.10');
        Setting::set('copies', 2);
        Setting::set('flags', ['a' => true]);

        $this->assertSame('192.168.0.10', Setting::get('printer_ip'));
        $this->assertSame(2, Setting::get('copies'));
        $this->assertSame(['a' => true], Setting::get('flags'));

        Setting::set('printer_ip', '192.168.0.11');
        $this->assertSame('192.168.0.11', Setting::get('printer_ip'));
        $this->assertSame(1, Setting::query()->where('key', 'printer_ip')->count());
    }

    public function test_pos_config_tax_rounding(): void
    {
        $this->assertSame('round', config('pos.tax_rounding.invoice'));
        $this->assertSame('floor', config('pos.tax_rounding.delivery'));
    }
}
