@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( '/common/dashboard-header' ) 
        <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
            @include( 'common.dashboard.title' )
            <ns-stock-adjustment :actions='@json( $actions )'></ns-stock-adjustment>
        </div>
    </div>
@endsection