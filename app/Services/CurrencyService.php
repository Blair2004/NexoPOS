<?php 
namespace App\Services;

use Illuminate\Support\Facades\Log;

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

    private static $_currency_iso           =   'USD';
    private static $_currency_symbol        =   '$';
    private static $_decimal_precision      =   2;
    private static $_thousand_separator     =   ',';
    private static $_decimal_separator      =   '.';
    private static $_currency_position      =   'before';
    private static $_prefered_currency      =   'iso';

    public function __construct( $value, $config = [])
    {
        $this->value                =   $value;

        extract( $config );

        $this->currency_iso         =   $currency_iso ?? self::$_currency_iso;
        $this->currency_symbol      =   $currency_symbol ?? self::$_currency_symbol;
        $this->currency_position    =   $currency_position ?? self::$_currency_position;
        $this->decimal_precision    =   $decimal_precision ?? self::$_decimal_precision;
        $this->decimal_separator    =   $decimal_separator ?? self::$_decimal_separator;
        $this->prefered_currency    =   $prefered_currency ?? self::$_prefered_currency;
        $this->thousand_separator   =   $thousand_separator ?? self::$_thousand_separator;
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
            'currency_iso'          =>  $this->currency_iso,
            'currency_symbol'       =>  $this->currency_symbol,
            'currency_position'     =>  $this->currency_position,
            'decimal_precision'     =>  $this->decimal_precision,
            'decimal_separator'     =>  $this->decimal_separator,
            'prefered_currency'     =>  $this->prefered_currency,
            'thousand_separator'    =>  $this->thousand_separator,
        ]);
    }

    /**
     * Set a value for the current instance
     * @param int|float $amount
     * @return CurrencyService
     */
    private static function __defineAmount( $amount ): CurrencyService
    {
        /**
         * @var CurrencyService
         */
        $currencyService    =   app()->make( CurrencyService::class );
        return $currencyService->value( $amount );
    }

    /**
     * Define an amount to work on
     * @param string
     * @return CurrencyService
     */
    public static function define( $amount )
    {
        return self::__defineAmount( $amount );
    }

    public function value( $amount )
    {
        $this->value    =   $amount;
        return $this;
    }

    public function __toString()
    {
        return $this->format();
    }

    /**
     * Multiply two numbers
     * and return a currency object
     * @param int left operand
     * @param int right operand
     * @return CurrencyService
     */
    public static function multiply( $first, $second )
    {
        return self::__defineAmount( 
            bcmul( floatval( trim( $first ) ), floatval( trim( $second ) ) )
        );
    }

    /**
     * Divide two numbers
     * and return a currency object
     * @param int left operand
     * @param int right operand
     * @return CurrencyService
     */
    public static function divide( $first, $second )
    {
        return self::__defineAmount( 
            bcdiv( floatval( trim( $first ) ), floatval( trim( $second ) ), 10 )
        );
    }

    /**
     * Additionnate two operands
     * @param int left operand
     * @param int right operand
     * @return CurrencyService
     */
    public static function additionate( $left_operand, $right_operand )
    {
        return self::__defineAmount(
            bcadd( floatval( $left_operand ), floatval( $right_operand ), 10 )
        );
    }

    /**
     * calculate a percentage of
     * 2 operand
     * @param int amount
     * @param int percentage
     * @return CurrencyService
     */
    public static function percent( $amount, $rate )
    {
        return self::__defineAmount(
            bcdiv( bcmul( floatval( $amount ), floatval( $rate ), intval( self::$_decimal_precision ) ), 100, intval( self::$_decimal_precision ) )
        );
    }

    /**
     * Define the currency in use
     * on the current process
     * @param string
     * @return Currency
     */
    public function currency( $currency )
    {
        $this->currency     =   $currency;
        return $this;
    }

    /**
     * Get a currency formatted
     * amount of the current Currency Object
     * @return string
     */
    public function format()
    {
        $currency   =   $this->prefered_currency === 'iso' ? $this->currency_iso : $this->currency_symbol;
        $final      =   sprintf( '%s ' . number_format( 
            $this->value, 
            $this->decimal_precision, 
            $this->decimal_separator, 
            $this->thousand_separator 
        ) . ' %s', 
            $this->currency_position === 'before' ? $currency  : '',
            $this->currency_position === 'after' ? $currency : ''
        );

        return $final;
    }

    /**
     * Get the current amount 
     * of the Currency Object
     * @return int|float
     */
    public function get()
    {
        return $this->getRaw( $this->value );
    }

    public function getRaw( $value = null )
    {
        return $this->bcround( ( $value === null ? $this->value : $value ), $this->decimal_precision );
    }

    /**
     * Will return the full raw without
     * rounding.
     * @return float $value
     */
    public function getFullRaw()
    {
        return $this->value;
    }

    /**
     * Define accuracy of the current
     * Currency object
     * @param int precision number
     * @return CurrencyService
     */
    public function accuracy( $number )
    {
        $this->decimal_precision    =   intval( $number );
        return $this;
    }

    /**
     * Multiply the current Currency value
     * by the provided number
     * @param int number to multiply by
     * @return CurrencyService
     */
    public function multipliedBy( $number )
    {
        $this->value    =   bcmul( floatval( $this->value ), floatval( $number ), 10 );
        return $this;
    }

    /**
     * Multiply the current Currency value
     * by the provided number
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
     * @param int number to divide by
     * @return CurrencyService
     */
    public function dividedBy( $number )
    {
        $this->value    =   bcdiv( floatval( $this->value ), floatval( $number ), 10 );
        return $this;
    }

    /**
     * Divide the current Currency Value
     * by the provided number
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
     * @param int number to subtract by
     * @return CurrencyService
     */
    public function subtractBy( $number )
    {
        $this->value    =   bcsub( floatval( $this->value ), floatval( $number ), 10 );
        return $this;
    }

    /**
     * Additionnate the current Currency Value
     * by the provided number
     * @param int number to additionnate by
     * @return CurrencyService
     */
    public function additionateBy( $number )
    {
        $this->value    =   bcadd( floatval( $this->value ), floatval( $number ), 10 );
        return $this;
    }

    /**
     * @source https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers
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
     * 
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

    public function bcround($number, $precision = 0)
    {
        if ( is_float( ( float ) $number ) ) {
            if ( ( ( string ) $number )[0] != '-') {
                $value     =   ( float ) bcadd( $number, '0.' . str_repeat('0', $precision) . '5', $precision);
            } else {
                $value     =   ( float ) bcsub( $number, '0.' . str_repeat('0', $precision) . '5', $precision);
            }

            if ( strpos( ( string ) $value, '.' ) === false ) {
                return ( int ) $value;
            }
            
            return $value;
        }

        return $number;
    }

    public function getPercentageValue( $value, $percentage, $operation = 'additionate' )
    {
        $percentage     =   CurrencyService::define( $value )
            ->multiplyBy( $percentage )
            ->dividedBy(100);

        if ( $operation === 'additionate' ) {
            return $value + $percentage;
        } else if ( $operation === 'subtract' ) {
            return $value - $percentage;
        }

        return $value;       
    }
}