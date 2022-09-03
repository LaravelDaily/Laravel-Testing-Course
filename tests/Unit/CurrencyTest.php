<?php

namespace Tests\Unit;

use App\Exceptions\CurrencyRateNotFoundException;
use App\Services\CurrencyService;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_convert_usd_to_eur_successful()
    {
        $this->assertEquals(98, (new CurrencyService())->convert(100, 'usd', 'eur'));
    }

    public function test_convert_usd_to_gbp_returns_zero()
    {
        $this->assertEquals(0, (new CurrencyService())->convert(100, 'usd', 'gbp'));
    }

    public function test_convert_gbp_to_usd_throws_exception()
    {
        $this->expectException(CurrencyRateNotFoundException::class);

        $this->assertEquals(0, (new CurrencyService())->convert(100, 'gbp', 'usd'));
    }
}
