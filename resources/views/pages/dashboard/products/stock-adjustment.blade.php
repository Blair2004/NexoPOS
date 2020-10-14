@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( '/common/dashboard-header' ) 
        <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
                <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
            </div>
            <ns-stock-adjusment></ns-stock-adjusment>
        </div>
    </div>
@endsection