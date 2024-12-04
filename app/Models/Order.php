<?php

namespace App\Models;

use App\Casts\DateCast;
use App\Casts\FloatConvertCasting;
use App\Classes\Hook;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Services\DateService;
use App\Traits\NsFlashData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ns-generated-comments
 *
 * @property int id
 * @property string description
 * @property string code
 * @property string title
 * @property string type
 * @property string payment_status
 * @property string process_status
 * @property string delivery_status
 * @property float discount
 * @property string discount_type
 * @property float discount_percentage
 * @property float shipping
 * @property float shipping_rate
 * @property string shipping_type
 * @property float total_without_tax
 * @property float subtotal
 * @property float total_with_tax
 * @property float total_coupons
 * @property float total_cogs
 * @property float total
 * @property float tax_value
 * @property float products_tax_value
 * @property float total_tax_value
 * @property int tax_group_id
 * @property string tax_type
 * @property float tendered
 * @property float change
 * @property string final_payment_date
 * @property string total_instalments
 * @property int customer_id
 * @property string note
 * @property string note_visibility
 * @property int author
 * @property string uuid
 * @property int register_id
 * @property string voidance_reason
 * @property string created_at
 * @property string updated_at
 *
 * @method from( $startRange )
 * @method to( $endRange )
 * @method paid( )
 * @method refunded( )
 * @method paymentStatus( )
 * @method paymentExpired( )
 */
class Order extends NsModel
{
    use HasFactory, NsFlashData;

    public $timestamps = false;

    protected $table = 'nexopos_' . 'orders';

    const TYPE_DELIVERY = 'delivery';

    const TYPE_TAKEAWAY = 'takeaway';

    const PAYMENT_PAID = 'paid';

    const PAYMENT_PARTIALLY = 'partially_paid';

    const PAYMENT_UNPAID = 'unpaid';

    const PAYMENT_HOLD = 'hold';

    const PAYMENT_VOID = 'order_void';

    const PAYMENT_REFUNDED = 'refunded';

    const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    const PAYMENT_DUE = 'due';

    const PAYMENT_PARTIALLY_DUE = 'partially_due';

    const PROCESSING_PENDING = 'pending';

    const PROCESSING_ONGOING = 'ongoing';

    const PROCESSING_READY = 'ready';

    const PROCESSING_FAILED = 'error';

    const DELIVERY_PENDING = 'pending';

    const DELIVERY_ONGOING = 'ongoing';

    const DELIVERY_FAILED = 'error';

    // @todo check if it's still useful
    const DELIVERY_COMPLETED = 'completed';

    const DELIVERY_DELIVERED = 'delivered';

    public $casts = [
        'final_payment_date' => DateCast::class,
        'support_instalments' => 'boolean',
        'discount' => FloatConvertCasting::class,
        'discount_percentage' => FloatConvertCasting::class,
        'shipping' => FloatConvertCasting::class,
        'shipping_rate' => FloatConvertCasting::class,
        'total_without_tax' => FloatConvertCasting::class,
        'subtotal' => FloatConvertCasting::class,
        'total_with_tax' => FloatConvertCasting::class,
        'total_coupons' => FloatConvertCasting::class,
        'total' => FloatConvertCasting::class,
        'total_cogs' => FloatConvertCasting::class,
        'tax_value' => FloatConvertCasting::class,
        'products_tax_value' => FloatConvertCasting::class,
        'total_tax_value' => FloatConvertCasting::class,
        'tendered' => FloatConvertCasting::class,
        'change' => FloatConvertCasting::class,
    ];

    public $dispatchesEvents = [
        'created' => OrderAfterCreatedEvent::class,
        'updated' => OrderAfterUpdatedEvent::class,
    ];

    protected $dispatchableFieldsEvents = [
        'payment_status' => OrderAfterPaymentStatusChangedEvent::class,
    ];

    public function products()
    {
        return $this->hasMany(
            OrderProduct::class,
            'order_id'
        );
    }

    public function refundedProducts()
    {
        return $this->hasMany(
            OrderProductRefund::class,
            'order_id'
        );
    }

    public function user()
    {
        return $this->hasOne( User::class, 'id', 'author' );
    }

    public function refunds()
    {
        return $this->hasMany( OrderRefund::class, 'order_id', 'id' );
    }

    public function payments()
    {
        return $this->hasMany( OrderPayment::class, 'order_id' );
    }

    public function customer()
    {
        return $this->hasOne( Customer::class, 'id', 'customer_id' );
    }

    public function taxes()
    {
        return $this->hasMany( OrderTax::class, 'order_id', 'id' );
    }

    public function coupons()
    {
        return $this->hasMany( OrderCoupon::class, 'order_id', 'id' );
    }

    public function instalments()
    {
        return $this->hasMany( OrderInstalment::class, 'order_id', 'id' );
    }

    public function shipping_address()
    {
        return $this->hasOne( OrderShippingAddress::class );
    }

    public function billing_address()
    {
        return $this->hasOne( OrderBillingAddress::class );
    }

    public function order_addresses()
    {
        return $this->hasMany( OrderAddress::class );
    }

    public function scopeFrom( $query, $range_starts )
    {
        return $query->where( 'created_at', '>=', $range_starts );
    }

    public function scopeTo( $query, $range_ends )
    {
        return $query->where( 'created_at', '<=', $range_ends );
    }

    public function scopePaid( $query )
    {
        return $query->where( 'payment_status', self::PAYMENT_PAID );
    }

    public function scopeRefunded( $query )
    {
        return $query->where( 'payment_status', self::PAYMENT_REFUNDED );
    }

    public function scopePaymentStatus( $query, $status )
    {
        return $query->where( 'payment_status', $status );
    }

    public function scopePaymentExpired( $query )
    {
        $date = app()->make( DateService::class );

        return $query
            ->whereIn( 'payment_status', [ Order::PAYMENT_PARTIALLY, Order::PAYMENT_UNPAID ] )
            ->where( 'final_payment_date', '<>', null )
            ->where( 'final_payment_date', '<', $date->now()->toDateTimeString() );
    }

    public function scopePaymentStatusIn( $query, array $statuses )
    {
        return $query->whereIn( 'payment_status', $statuses );
    }

    /**
     * Will return conditionnaly the merged products
     * or all the product if it's enabled or disabled.
     *
     * @return array
     */
    public function getCombinedProductsAttribute()
    {
        if ( ns()->option->get( 'ns_invoice_merge_similar_products', 'no' ) === 'yes' ) {
            $combinaison = [];

            $this->products()->with( 'unit' )->get()->each( function ( $product ) use ( &$combinaison ) {
                $values = $product->toArray();

                extract( $values );

                $keys = array_keys( $combinaison );
                $stringified = Hook::filter( 'ns-products-combinaison-identifier', $product_id . '-' . $order_id . '-' . $discount . '-' . $product_category_id . '-' . $status, $product );
                $combinaisonAttributes = Hook::filter( 'ns-products-combinaison-attributes', [
                    'quantity',
                    'total_price_without_tax',
                    'total_price',
                    'total_purchase_price',
                    'total_price_with_tax',
                    'discount',
                ] );

                if ( in_array( $stringified, $keys ) ) {
                    foreach ( $combinaisonAttributes as $attribute ) {
                        $combinaison[ $stringified ][ $attribute ] += (float) $product->$attribute;
                    }
                } else {
                    $rawProduct = $product->toArray();

                    unset( $rawProduct[ 'id' ] );
                    unset( $rawProduct[ 'created_at' ] );
                    unset( $rawProduct[ 'updated_at' ] );
                    unset( $rawProduct[ 'procurement_product_id' ] );

                    $combinaison[ $stringified ] = $rawProduct;
                }
            } );

            /**
             * that's nasty.
             */
            return collect( json_decode( json_encode( $combinaison ) ) );
        }

        return $this->products()->get();
    }
}
