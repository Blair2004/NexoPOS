<?php

use App\Classes\Hook;
use App\Models\Order;

?>
@extends( 'layout.dashboard' )

<?php
$isDue  =   in_array( $order->payment_status, [ Order::PAYMENT_PARTIALLY, Order::PAYMENT_UNPAID ] );
?>

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @include( 'common.dashboard.title' )
        <div class="my-2">
            <ns-order-invoice 
                :shipping='@json( $shipping )'
                :billing='@json( $billing )'
                :order='@json( $order )'></ns-order-invoice>
        </div>
    </div>
</div>
@endsection