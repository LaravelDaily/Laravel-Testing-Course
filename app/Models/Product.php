<?php

namespace App\Models;

use App\Exceptions\CurrencyRateNotFoundException;
use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'published_at', 'photo'];

    public function getPriceEurAttribute()
    {
        try {
            return (new CurrencyService())->convert($this->price, 'usd', 'eur');
        } catch (CurrencyRateNotFoundException $ex) {
            // Log something, alert someone
            return 0;
        }
    }

    public function price(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value * 100
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
