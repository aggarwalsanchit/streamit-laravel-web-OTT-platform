<?php

namespace Modules\Currency\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'currencies';
     protected $fillable = [
        'currency_name', 'currency_symbol', 'currency_code', 'currency_position', 'no_of_decimal', 'thousand_separator', 'decimal_separator', 'is_primary',
    ];
    const CUSTOM_FIELD_MODEL = 'Modules\Currency\Models\Currency';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */


    protected $appends = ['feature_image'];

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');
        return isset($media) && ! empty($media) ? $media : 'https://dummyimage.com/600x300/cfcfcf/000000.png';
    }
    protected static function newFactory()
    {
        return \Modules\Currency\database\factories\CurrencyFactory::new();
    }

    public static function getDefaultCurrency($asArray = false)
    {
        $currency = self::where('is_primary', 1)->first();
        if (!$currency) {
            $currency = self::first();
        }

        if ($asArray && $currency) {
            return $currency->toArray();
        }

        return $currency;
    }

    public static function format($value)
    {
        $currency = self::getDefaultCurrency();

        if (!$currency) {
            return '$' . number_format($value, 2);
        }

        return formatCurrency(
            $value,
            $currency->no_of_decimal,
            $currency->decimal_separator,
            $currency->thousand_separator,
            $currency->currency_position,
            $currency->currency_symbol
        );
    }

    public static function defaultSymbol()
    {
        $currency = self::getDefaultCurrency();
        return $currency ? $currency->currency_symbol : '$';
    }

}
