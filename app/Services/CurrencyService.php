<?php

namespace App\Services;

use App\Exceptions\CurrencyRateNotFoundException;
use Illuminate\Support\Arr;

class CurrencyService
{
    const RATES = [
        'usd' => [
            'eur' => 0.98
        ]
    ];

    public function convert(float $amount, string $currencyFrom, string $currencyTo): float
    {
        if (! Arr::exists(self::RATES, $currencyFrom)) {
            throw new CurrencyRateNotFoundException('Currency rate not found');
        }

        $rate = self::RATES[$currencyFrom][$currencyTo] ?? 0;

        return round($amount * $rate, 2);
    }

}
