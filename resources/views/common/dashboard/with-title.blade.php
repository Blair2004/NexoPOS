<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
            <p class="text-gray-600">{{ $description ?? __( 'No description' ) }}</p>
        </div>
        @yield( 'layout.dashboard.body.with-title' )
    </div>
</div>