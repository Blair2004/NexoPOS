<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @include( 'common.dashboard.title' )
        @yield( 'layout.dashboard.body.with-title' )
        @yield( 'layout.dashboard.with-title' )
    </div>
</div>