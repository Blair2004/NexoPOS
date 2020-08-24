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
                <div class="header-tabs flex -mx-4 flex-wrap">
                    <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="#">{{ __( 'Enabled' ) }} (10)</a></div>
                    <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="#">{{ __( 'Disabled ' ) }} (10)</a></div>
                </div>
                <div class="">
                    <a href="{{ route( 'ns.dashboard.modules.upload' ) }}" class="rounded-lg text-gray-600 bg-white shadow px-3 py-1 hover:bg-blue-400 hover:text-white">{{ __( 'Upload' ) }} <i class="las la-angle-right"></i></a>
                </div>
            </div>
            <div class="module-section flex flex-wrap py-4 -my-4 -mx-4">
                @foreach( $modules as $module )
                <div class="px-4 w-full md:w-1/2 lg:w-1/3 py-4">
                    <div class="rounded shadow overflow-hidden">
                        <div class="module-head h-40 p-2 bg-white">
                            <h3 class="font-semibold text-lg text-gray-700">NexoPOS</h3>
                            <p class="text-gray-600 text-xs">Blair Jersyer - v1.5</p>
                            <p class="py-2 text-gray-700 text-sm">Lorem ipsum dolor sit amet consectetur adipisicing elit. Ipsa officiis deserunt velit culpa aut laboriosam ipsam cum nulla</p>
                        </div>
                        <div class="footer bg-gray-200 p-2 flex justify-between">
                            <ns-button type="info">{{ __( 'Enable' ) }}</ns-button>
                            <ns-button type="danger"><i class="las la-trash"></i></ns-button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection