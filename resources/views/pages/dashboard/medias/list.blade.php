@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col flex-auto">
    @include( '../common/dashboard-header' )
    <div class="flex-auto flex flex-col overflow-hidden" id="dashboard-content">
        <ns-media></ns-media>
    </div>
</div>
@endsection