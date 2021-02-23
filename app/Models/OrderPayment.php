<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderPayment extends NsModel
{
    use HasFactory;
    
    protected $table        =   'nexopos_' . 'orders_payments';

    const PAYMENT_CASH      =   'cash-payment';
    const PAYMENT_ACCOUNT   =   'account-payment';
    const PAYMENT_BANK      =   'bank-payment';

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id', 'id' );
    }

    public function scopeWithOrder( $query, $order_id )
    {
        return $query->where( 'order_id', $order_id );
    }

    public function getPaymentLabelAttribute()
    {
        $paymentTypes   =   collect( config( 'nexopos.pos.payments' ) )->mapWithKeys( function( $payment ) {
            return [ $payment[ 'identifier' ] => $payment[ 'label' ] ];
        });

        return $paymentTypes[ $this->identifier ] ?? __( 'Unknown Payment' );
    }
}