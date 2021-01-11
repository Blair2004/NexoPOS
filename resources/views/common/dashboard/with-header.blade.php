<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @yield( 'layout.dashboard.body.with-header' )
    </div>
</div>