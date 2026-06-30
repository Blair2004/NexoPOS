@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col flex-auto">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col overflow-hidden" id="dashboard-content">
        @if ( ns()->option->get( 'ns_media_library_layout', 'modern' ) === 'legacy' )
            <ns-media></ns-media>
        @else
            <ns-media-library></ns-media-library>
        @endif
    </div>
</div>
@endsection
