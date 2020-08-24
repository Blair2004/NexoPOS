@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col">
    @include( '../common/dashboard-header' )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
                <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
            </div>
        </div>
        <div>
            <div class="flex justify-between items-center">
                <div class="">
                    <a href="{{ route( 'ns.dashboard.modules.list' ) }}" class="rounded-lg text-gray-600 bg-white shadow px-3 py-1 hover:bg-blue-400 hover:text-white"><i class="las la-angle-left"></i> {{ __( 'Return' ) }}</a>
                </div>
            </div>
            <div class="module-section bg-white h-56 my-4 flex items-center justify-center shadow">
                <div class="flex flex-col">
                    <h2 class="text-gray-600 font-bold">{{ __( 'Drop Your Module Here' ) }}</h2>
                    <p class="text-xs text-gray-500 text-center">{{ __( 'Or click to load a file.' ) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection