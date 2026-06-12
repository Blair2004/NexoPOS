@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col flex-auto" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <ns-marketplace :authenticate="{{ request()->has( 'action' ) && request()->input( 'action' ) === 'authenticate' }}" :is-connected="{{ $isConnected ? 'true' : 'false' }}"></ns-marketplace>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    <script>
        const authorizationUrl = '{{ $authorizationUrl }}';
    </script>
    @parent
@endsection