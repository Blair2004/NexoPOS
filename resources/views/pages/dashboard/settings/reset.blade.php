<?php

use App\Classes\Hook;
use App\Services\Helper;

?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
        @include( 'common.dashboard.title' )
        </div>
        <div>
            <ns-reset></ns-reset>
        </div>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
<script>
    const ResetData     =   {
        options: <?php echo json_encode( Helper::kvToJsOptions( Hook::filter( 'ns-reset-options', [
            'wipe_all'              =>  __( 'Wipe All' ),
            'wipe_plus_grocery'     =>  __( 'Wipe Plus Grocery' ),
        ])));?>
    }
</script>
@endsection