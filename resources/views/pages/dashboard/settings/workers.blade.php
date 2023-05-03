@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
        @include( 'common.dashboard.title' )
        </div>
        <div>
            <ns-settings url="{{ ns()->url( '/api/settings/ns.workers' ) }}"></ns-settings>
        </div>
    </div>
</div>
@endsection