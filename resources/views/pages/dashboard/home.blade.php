<?php

use App\Classes\Hook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
        <?php 
            $dashid     =   Auth::user()->role->dashid; 
            $dashviews  =   Hook::filter( 'ns-dashboard-view', [
                'store'     =>  'pages.dashboard.store-dashboard',
                'default'   =>  'pages.dashboard.default-dashboard',
                'cashier'   =>  'pages.dashboard.cashier-dashboard'
            ]);
        ?>
        @include( $dashviews[ $dashid ] )
    </div>
@endsection

@section( 'layout.dashboard.footer.inject' )
    @if ( $dashid === 'store' )
    <script src="{{ asset( '/js/dashboard.js' ) }}"></script>
    @elseif ( $dashid === 'cashier' )
    <script src="{{ asset( '/js/cashier.js' ) }}"></script>
    @endif
@endsection