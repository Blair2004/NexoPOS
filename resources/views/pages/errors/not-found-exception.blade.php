@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div class="h-full w-full overflow-y-auto pb-10 bg-gradient-to-bl from-purple-300 to-purple-700 flex items-center justify-center">
        <div class="w-full md:w-1/2 xl:1/3 flex items-center flex-col justify-center">
            <span class="rounded-full text-6xl w-24 h-24 flex items-center justify-center bg-white shadow text-purple-500 mb-4"><i class="las la-frown"></i></span>
            <h1 class="text-white text-3xl lg:text-5xl font-bold text-center">{!! $title ?? __( '404 Error' ) !!}</h1>
            <div class="rounded-lg p-4 bg-white my-4 md:w-1/2 shadow">
                <div class="p-4 text-primary text-center text-sm">
                    {{ __( 'We\'re unable to locate the page you\'re searching for. This page was moved, deleted or simply invalid. You can start by giving another shot clicking on "Try Again". If the issue persist, uses the bellow output to receive support.' ) }}
                </div>
                <p class="md:w-auto w-95vw bg-gray-700 text-gray-100 lg:text-lg text-center p-4 my-2">{!! $message !!}</p>
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
                        <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url( '/' ) }}"><i class="las la-home"></i> {{ __( 'Home' ) }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection