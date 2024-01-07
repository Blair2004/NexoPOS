@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div class="h-full w-full overflow-y-auto pb-10 bg-gradient-to-bl from-pink-400 to-blue-400 flex items-center justify-center">
        <div class="w-full md:w-1/2 xl:1/3 flex items-center flex-col justify-center">
            <span class="rounded-full text-6xl w-24 h-24 flex items-center justify-center bg-white shadow text-red-500 mb-4"><i class="las la-car-crash"></i></span>
            <h1 class="text-white text-3xl lg:text-5xl font-bold text-center">{!! $title ?? __( 'Missing Dependency' ) !!}</h1>
            <p class="py-3 text-white lg:text-lg text-center px-4 lg:px-0 w-full lg:w-2/3">{{ $message }}</p>
            <div class="flex md:flex-row flex-col -mx-4 my-4 flex-wrap w-56 lg:w-auto">
                <div class="px-4 mb-4">
                    <div class="ns-button hover-info">
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ $back }}"><i class="las la-car-crash"></i> {{ __( 'Go Back' ) }}</a>
                    </div>
                </div>
                <div class="px-4 mb-4">
                    <div class="ns-button hover-info">
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url()->current() . '?back=' . urlencode( request()->query( 'back' ) ?? url()->previous() )  }}"><i class="las la-sync"></i> {{ __( 'Continue' ) }}</a>
                    </div>
                </div>
                <div class="px-4 mb-4">
                    <div class="ns-button hover-info">
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url( '/' ) }}"><i class="las la-home"></i> {{ __( 'Home' ) }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection