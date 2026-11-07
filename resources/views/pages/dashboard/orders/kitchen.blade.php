<?php
use App\Services\Helper;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Kitchen Display' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Orders are sorted by time. Dishes are grouped by kitchen zone (product category). The screen refreshes automatically.' ) }}</p>
        </div>
        <ns-kitchen-display
            :config="{{ json_encode( $config ) }}">
        </ns-kitchen-display>
    </div>
</div>
@endsection
