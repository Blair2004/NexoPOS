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
            $roles              =   Auth::user()->roles;

            $definedDashboard   =   $roles->filter( fn( $role ) => $role->dashid !== 'none' )
                ->map( fn( $role ) => $role->dashid );

            $noDashboard        =   $roles->filter( fn( $role ) => $role->dashid === 'none' )->count() === $roles->count();

            if ( $noDashboard ) {
                $dashid     =   'none';
            } else if ( $definedDashboard->count() > 1 ) {
                $dashid     =   'conflict';
            } else {
                $dashid     =   $definedDashboard->first();
            }

            $dashviews  =   Hook::filter( 'ns-dashboard-view', [
                'none'      =>  'pages.dashboard.no-dashboard',
                'conflict'  =>  'pages.dashboard.conflicting-dashboard',
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
    <script src="{{ asset( ns()->isProduction() ? '/js/dashboard.min.js' : '/js/dashboard.js' ) }}"></script>
    @elseif ( $dashid === 'cashier' )
    <script src="{{ asset( ns()->isProduction() ? '/js/cashier.min.js' : '/js/cashier.js' ) }}"></script>
    @endif
@endsection