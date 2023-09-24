<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class CurrencyService
{
    private $value;

    private $currency_iso;

    private $prefered_currency;

    private $currency_symbol;

    private $format;

    private $decimal_precision;

    private $thousand_separator;

    private $decimal_separator;

    private static $_currency_iso = 'USD';

    private static $_currency_symbol = '$';

    private static $_decimal_precision = 2;

    private static $_thousand_separator = ',';

    private static $_decimal_separator = '.';

    private static $_currency_position = 'before';

    private static $_prefered_currency = 'iso';

    public function __construct( $value, $config = [])
    {
        $this->value = BigDecimal::of( $value );

        extract( $config );

        $this->currency_iso = $currency_iso ?? self::$_currency_iso;
        $this->currency_symbol = $currency_symbol ?? self::$_currency_symbol;
        $this->currency_position = $currency_position ?? self::$_currency_position;
        $this->decimal_precision = $decimal_precision ?? self::$_decimal_precision;
        $this->decimal_separator = $decimal_separator ?? self::$_decimal_separator;
        $this->prefered_currency = $prefered_currency ?? self::$_prefered_currency;
        $this->thousand_separator = $thousand_separator ?? self::$_thousand_separator;
    }

    /**
     * Will intanciate a new instance
     * using the default value
     *
     * @param int|float $value
     * @return CurrencyService
     */
    public function fresh( $value )
    {
        return new CurrencyService( $value, [
            'currency_iso' => $this->currency_iso,
            'currency_symbol' => $this->currency_symbol,
            'currency_position' => $this->currency_position,
            'decimal_precision' => $this->decimal_precision,
            'decimal_separator' => $this->decimal_separator,
            'prefered_currency' => $this->prefered_currency,
            'thousand_separator' => $this->thousand_separator,
        ]);
    }

    /**
     * Set a value for the current instance
     *
     * @param int|float $amount
     */
    private static function __defineAmount( $amount ): CurrencyService
    {
        /**
         * @var CurrencyService
         */
        $currencyService = app()->make( CurrencyService::class );

        return $currencyService->value( $amount );
    }

    /**
     * Define an amount to work on
     *
     * @param string
     * @return CurrencyService
     */
    public static function define( $amount )
    {
        return self::__defineAmount( $amount );
    }

    public function value( $amount )
    {
        $this->value = BigDecimal::of( $amount );

        return $this;
    }

    public function __toString()
    {
        return $this->format();
    }

    /**
     * Multiply two numbers
     * and return a currency object
     *
     * @param int left operand
     * @param int right operand
     * @return CurrencyService
     */
    public static function multiply( $first, $second )
    {
        return self::__defineAmount(
            BigDecimal::of( trim( $first ) )
        )->multipliedBy( trim( $second ) );
    }

    /**
     * Divide two numbers
     * and return a currency object
     *
     * @param int left operand
     * @param int right operand
     * @return CurrencyService
     */
    public static function divide( $first, $second )
    {
        return self::__defineAmount(
            BigDecimal::of( $first )
        )->dividedBy( $second );
    }

    /**
     * Additionnate two operands
     *
     * @param int left operand
     * @param int right operand
     * @return CurrencyService
     */
    public static function additionate( $left_operand, $right_operand )
    {
        return self::__defineAmount(
            BigDecimal::of( $left_operand )
        )->additionateBy( $right_operand );
    }

    /**
     * calculate a percentage of
     * 2 operand
     *
     * @param int amount
     * @param int percentage
     * @return CurrencyService
     */
    public static function percent( $amount, $rate )
    {
        return self::__defineAmount( BigDecimal::of( $amount ) )
            ->multipliedBy( $rate )
            ->dividedBy(100);
    }

    /**
     * Define the currency in use
     * on the current process
     *
     * @param string
     * @return Currency
     */
    public function currency( $currency )
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get a currency formatted
     * amount of the current Currency Object
     *
     * @return string
     */
    public function format()
    {
        $currency = $this->prefered_currency === 'iso' ? $this->currency_iso : $this->currency_symbol;
        $final = sprintf( '%s ' . number_format(
            floatval( (string) $this->value ),
            $this->decimal_precision,
            $this->decimal_separator,
            $this->thousand_separator
        ) . ' %s',
            $this->currency_position === 'before' ? $currency : '',
            $this->currency_position === 'after' ? $currency : ''
        );

        return $final;
    }

    /**
     * Get the current amount
     * of the Currency Object
     *
     * @return int|float
     */
    public function get()
    {
        return $this->getRaw( $this->value );
    }

    /**
     * return a raw value for the provided number
     *
     * @param float $value
     * @return float
     */
    public function getRaw( $value = null )
    {
        if ( $value === null ) {
            return $this->value->dividedBy( 1, $this->decimal_precision, RoundingMode::HALF_UP )->toFloat();
        } else {
            return BigDecimal::of( $value )->dividedBy( 1, $this->decimal_precision, RoundingMode::HALF_UP )->toFloat();
        }

        return 0;
    }

    /**
     * Define accuracy of the current
     * Currency object
     *
     * @param int precision number
     * @return CurrencyService
     */
    public function accuracy( $number )
    {
        $this->decimal_precision = intval( $number );

        return $this;
    }

    /**
     * Multiply the current Currency value
     * by the provided number
     *
     * @param int number to multiply by
     * @return CurrencyService
     */
    public function multipliedBy( $number )
    {
        $this->value = $this->value->multipliedBy( $number );

        return $this;
    }

    /**
     * Multiply the current Currency value
     * by the provided number
     *
     * @param int number to multiply by
     * @return CurrencyService
     */
    public function multiplyBy( $number )
    {
        return $this->multipliedBy( $number );
    }

    /**
     * Divide the current Currency Value
     * by the provided number
     *
     * @param int number to divide by
     * @return CurrencyService
     */
    public function dividedBy( $number )
    {
        $this->value = $this->value->dividedBy( $number, $this->decimal_precision, RoundingMode::UP );

        return $this;
    }

    /**
     * Divide the current Currency Value
     * by the provided number
     *
     * @param int number to divide by
     * @return CurrencyService
     */
    public function divideBy( $number )
    {
        return $this->dividedBy( $number );
    }

    /**
     * Subtract the current Currency Value
     * by the provided number
     *
     * @param int number to subtract by
     * @return CurrencyService
     */
    public function subtractBy( $number )
    {
        $this->value = $this->value->minus( $number );

        return $this;
    }

    /**
     * Additionnate the current Currency Value
     * by the provided number
     *
     * @param int number to additionnate by
     * @return CurrencyService
     */
    public function additionateBy( $number )
    {
        $this->value = $this->value->plus( $number );

        return $this;
    }

    /**
     * @source https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers
     *
     * @deprecated
     */
    public function bcceil( $number )
    {
        if ( strpos( $number, '.' ) !== false) {
            if (preg_match("~\.[0]+$~", $number ) ) {
                return $this->bcround( $number, 0 );
            }

            if ( $number[0] != '-') {
                return bcadd( $number, 1, 0);
            }

            return bcsub( $number, 0, 0 );
        }

        return $number;
    }

    /**
     * @deprecated
     */
    public function bcfloor( $number )
    {
        if ( strpos( $number, '.' ) !== false) {
            if (preg_match("~\.[0]+$~", $number)) {
                return $this->bcround($number, 0);
            }

            if ($number[0] != '-') {
                return bcadd($number, 0, 0);
            }

            return bcsub($number, 1, 0);
        }

        return $number;
    }

    /**
     * @deprecated
     */
    public function bcround($number, $precision = 0)
    {
        if ( is_float( (float) $number ) ) {
            if ( ( (string) $number )[0] != '-') {
                $value = (float) bcadd( $number, '0.' . str_repeat('0', $precision) . '5', $precision);
            } else {
                $value = (float) bcsub( $number, '0.' . str_repeat('0', $precision) . '5', $precision);
            }

            if ( strpos( (string) $value, '.' ) === false ) {
                return (int) $value;
            }

            return $value;
        }

        return $number;
    }

    public function getPercentageValue( $value, $percentage, $operation = 'additionate' )
    {
        $percentage = CurrencyService::define( $value )
            ->multiplyBy( $percentage )
            ->dividedBy(100);

        if ( $operation === 'additionate' ) {
            return (float) BigDecimal::of( $value )->plus( $percentage );
        } elseif ( $operation === 'subtract' ) {
            return (float) BigDecimal::of( $value )->minus( $percentage );
        }

        return $value;
    }
}
