@extends( 'layout.default' )

@section( 'layout.default.body' )
    <div class="h-full w-full overflow-y-auto pb-10 bg-gradient-to-bl from-red-500 to-pink-500 flex items-center justify-center">
        <div class="w-full md:w-1/2 xl:1/3 flex items-center flex-col justify-center">
            <span class="rounded-full text-6xl w-24 h-24 flex items-center justify-center bg-white shadow text-red-500 mb-4">
                <i class="las la-unlink"></i>
            </span>
            <h1 class="text-white text-3xl mb-2 lg:text-5xl font-bold text-center">{!! $title ?? __( 'Not Allowed Action' ) !!}</h1>
            <div class="my-2 shadow overflow-hidden rounded bg-white w-95vw md:w-3/4 lg:w-3/5">
                <p class="md:w-auto w-95vw bg-gray-700 text-gray-100 lg:text-lg text-center p-4">{{ $message }}</p>
                @if ( ns()->isProduction() ) 
                <div class="p-4 text-gray-600 text-center text-sm">
                    {{ __( 'Your system is running in production mode. You probably need to build the assets' ) }}
                </div>
                @else
                <div class="p-4 text-gray-600 text-center text-sm">
                    {{ __( 'Your system is in development mode. Make sure to build the assets.' ) }}
                </div>
                @endif
                <ul class="flex flex-col">
                    <li class="flex">
                        <a target="_blank" href="https://my.nexopos.com/en/documentation/how-tos/how-to-change-database-configuration" class="border-t w-full border-gray-300 text-gray-600 text-sm p-2">
                            <i class="las la-hand-point-right"></i>
                            <span class="ml-2">{{ __( 'How to change database configuration' ) }}</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="flex md:flex-row flex-col -mx-4 my-4 flex-wrap w-56 lg:w-auto">
                <div class="px-4 mb-4">
                    <div class="ns-button hover-info">
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ $back }}"><i class="las la-angle-left"></i> {{ __( 'Go Back' ) }}</a>
                    </div>
                </div>
                <div class="px-4 mb-4">
                    <div class="ns-button hover-info">
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url()->current() . '?back=' . urlencode( request()->query( 'back' ) ?? url()->previous() )  }}"><i class="las la-sync"></i> {{ __( 'Try Again' ) }}</a>
                    </div>
                </div>
                <div class="px-4 mb-4">
                    <div class="ns-button hover-info">
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url( '/do-setup' ) }}"><i class="las la-cog"></i> {{ __( 'Setup' ) }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection