@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col flex-auto" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <div class="flex-auto flex h-full w-full">
            <ns-modules 
                upload="{{ url( 'dashboard/modules/upload' ) }}"
                url="{{ url( 'api/modules' ) }}"></ns-modules>
        </div>
    </div>
</div>
@endsection