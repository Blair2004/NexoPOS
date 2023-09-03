@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <ns-profit-report 
            storeName="{{ ns()->option->get( 'ns_store_name' ) }}" 
            storeLogo="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" 
            v-cloak>            
        </ns-profit-report>
    </div>
</div>
@endsection