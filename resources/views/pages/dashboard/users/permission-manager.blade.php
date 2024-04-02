@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <div class="pb-4">
            <ns-permissions></ns-permissions>
        </div>
    </div>
</div>
@endsection