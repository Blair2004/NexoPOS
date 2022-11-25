<?php

use App\Classes\Hook;
use App\Classes\Output;
use Illuminate\Support\Facades\Auth;

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
        <?php 
            $output     =   new Output;
            Hook::action( 'ns-dashboard-home-footer', $output );
            echo ( string ) $output;
        ?>
        @vite([ 'resources/ts/widgets.ts' ])
        @vite([ 'resources/ts/dashboard.ts' ])
    @elseif ( $dashid === 'cashier' )
        @vite([ 'resources/ts/cashier.ts' ])
    @endif
@endsection