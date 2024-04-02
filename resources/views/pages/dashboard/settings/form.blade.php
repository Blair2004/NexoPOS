<?php

use App\Classes\Hook;
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
            @include( 'common.dashboard.title' )
        </div>
        <div>
            <ns-settings
                url="{{ ns()->url( '/api/settings/' . $identifier ) }}">
            </ns-settings>
        </div>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
    <?php
    $output     =   new Output;
    Hook::action( 'ns-dashboard-settings-footer', $output, $identifier )
    ?>
    {!! ( string ) $output !!}
@endsection