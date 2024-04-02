<?php
use App\Classes\Hook;
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <div>
            <ns-settings
                url="{{ $src ?? '#' }}"
                submit-url="{{ $submitUrl }}">
            </ns-settings>
        </div>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
    {!! ( string ) Hook::filter( 'ns-profile-footer', new Output ) !!}
@endsection