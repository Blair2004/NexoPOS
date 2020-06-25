<?php 
namespace App\Services;

use Illuminate\Support\Facades\Log;

class CurrencyService
{
    private $value;

    private $currency;
    private $format;
    private $decimal_precision;
    private $thousand_separator;
    private $decimal_separator;

    private static $_currency               =   'USD';
    private static $_decimal_precision      =   2;
    private static $_thousand_separator     =   ',';
    private static $_decimal_separator      =   '.';

    public function __construct( $value, $config = [])
    {
        $this->value                =   $value;

        extract( $config );

        $this->decimal_precision    =   $decimal_precision ?: self::$_decimal_precision;
        $this->currency             =   $currency ?: self::$_currency;
    }

    private static function __defineAmount( $amount )
    {
        return new CurrencyService( $amount, [
            'decimal_precision'     =>  self::$_decimal_precision,
            'thousand_separator'    =>  self::$_thousand_separator,
            'decimal_separator'     =>  self::$_decimal_separator,
            'currency'              =>  self::$_currency
        ]);
    }

    /**
     * Define an amount to work on
     * @param string
     * @return Currency
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
            bcmul( floatval( trim( $first ) ), floatval( trim( $second ) ), intval( self::$_decimal_precision ) )
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
            bcdiv( floatval( trim( $first ) ), floatval( trim( $second ) ), intval( self::$_decimal_precision ) )
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
            bcadd( floatval( $left_operand ), floatval( $right_operand ), intval( self::$_decimal_precision ) )
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
        return number_format( 
            $this->value, 
            $this->decimal_precision, 
            $this->decimal_separator, 
            $this->thousand_separator 
        );
    }

    /**
     * Get the current amount 
     * of the Currency Object
     * @return int|float
     */
    public function get()
    {
        $value  =   floatval( number_format( 
            $this->value, 
            $this->decimal_precision, 
            '.', 
            ''
        ) );
        // Log::info( 'CurrencyService Value', compact( 'value' ) );
        return floatval( $value );
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
        $this->value    =   bcmul( floatval( $this->value ), floatval( $number ), $this->decimal_precision );
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
        $this->value    =   bcdiv( floatval( $this->value ), floatval( $number ), $this->decimal_precision );
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
        $this->value    =   bcsub( floatval( $this->value ), floatval( $number ), $this->decimal_precision );
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
        $this->value    =   bcadd( floatval( $this->value ), floatval( $number ), $this->decimal_precision );
        return $this;
    }
}