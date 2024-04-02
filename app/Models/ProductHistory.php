<?php

namespace App\Models;

use App\Casts\FloatConvertCasting;
use App\Events\ProductHistoryAfterCreatedEvent;
use App\Events\ProductHistoryAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $product_id
 * @property int            $procurement_id
 * @property int            $procurement_product_id
 * @property int            $order_id
 * @property int            $order_product_id
 * @property mixed          $operation_type
 * @property int            $unit_id
 * @property float          $before_quantity
 * @property float          $quantity
 * @property float          $after_quantity
 * @property float          $unit_price
 * @property float          $total_price
 * @property string         $description
 * @property int            $author
 * @property mixed          $uuid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Product        $product
 */
class ProductHistory extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'products_histories';

    const ACTION_STOCKED = 'procured';

    const ACTION_DELETED = 'deleted';

    const ACTION_TRANSFER_OUT = 'outgoing-transfer';

    const ACTION_TRANSFER_IN = 'incoming-transfer';

    const ACTION_TRANSFER_REJECTED = 'transfer-rejected';

    const ACTION_TRANSFER_CANCELED = 'transfer-canceled';

    const ACTION_REMOVED = 'removed';

    const ACTION_ADDED = 'added';

    const ACTION_SOLD = 'sold';

    const ACTION_RETURNED = 'returned';

    const ACTION_DEFECTIVE = 'defective';

    const ACTION_LOST = 'lost';

    const ACTION_VOID_RETURN = 'void-return';

    const ACTION_ADJUSTMENT_RETURN = 'return-adjustment';

    const ACTION_ADJUSTMENT_SALE = 'sale-adjustment';

    const ACTION_CONVERT_OUT = 'convert-out';

    const ACTION_CONVERT_IN = 'convert-in';

    const ACTION_SET = 'set';

    public $casts = [
        'before_quantity' => FloatConvertCasting::class,
        'quantity' => FloatConvertCasting::class,
        'after_quantity' => FloatConvertCasting::class,
    ];

    public $dispatchesEvents = [
        'created' => ProductHistoryAfterCreatedEvent::class,
        'updated' => ProductHistoryAfterUpdatedEvent::class,
    ];

    /**
     * actions that reduce stock
     */
    const STOCK_REDUCE = [
        ProductHistory::ACTION_TRANSFER_OUT,
        ProductHistory::ACTION_REMOVED,
        ProductHistory::ACTION_SOLD,
        ProductHistory::ACTION_DEFECTIVE,
        ProductHistory::ACTION_LOST,
        ProductHistory::ACTION_ADJUSTMENT_SALE,
        ProductHistory::ACTION_DELETED,
        ProductHistory::ACTION_CONVERT_OUT,
    ];

    /**
     * actions that increase stock
     */
    const STOCK_INCREASE = [
        ProductHistory::ACTION_ADDED,
        ProductHistory::ACTION_RETURNED,
        ProductHistory::ACTION_TRANSFER_IN,
        ProductHistory::ACTION_STOCKED,
        ProductHistory::ACTION_VOID_RETURN,
        ProductHistory::ACTION_TRANSFER_REJECTED,
        ProductHistory::ACTION_TRANSFER_CANCELED,
        ProductHistory::ACTION_ADJUSTMENT_RETURN,
        ProductHistory::ACTION_CONVERT_IN,
    ];

    /**
     * alias of scopeFindProduct
     *
     * @param  QueryBuilder $query
     * @param  int          $product_id
     * @return QueryBuilder
     */
    public function scopeWithProduct( $query, $product_id )
    {
        return $query->where( 'product_id', $product_id );
    }

    public function scopeFindProduct( $query, $id )
    {
        return $query->where( 'product_id', $id );
    }

    public function unit()
    {
        return $this->belongsTo( Unit::class );
    }

    public function product()
    {
        return $this->belongsTo( Product::class );
    }
}
