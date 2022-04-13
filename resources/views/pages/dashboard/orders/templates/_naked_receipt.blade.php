<?php
use App\Classes\Hook;
?>
@extends( 'layout.base' )
@section( 'layout.base.body' )
    @include( Hook::filter( 'ns-web-receipt-template', 'pages.dashboard.orders.templates._receipt' ) )
@endsection