@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( '../common/dashboard-header' )
    <div class="px-4 flex flex-col flex-auto" id="dashboard-content">
        <div class="flex flex-col">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
                <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
            </div>
        </div>
        <div class="flex-auto flex h-full w-full">
            <ns-modules 
                upload="{{ url( 'dashboard/modules/upload' ) }}"
                url="{{ url( 'api/nexopos/v4/modules' ) }}"></ns-modules>
        </div>
    </div>
</div>
@endsection