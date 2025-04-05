<?php
use App\Classes\Hook;
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
        @include( 'pages.dashboard.store-dashboard' )
    </div>
@endsection

@section( 'layout.dashboard.footer.inject' )
    @vite([ 'resources/ts/widgets.ts' ])
@endsection