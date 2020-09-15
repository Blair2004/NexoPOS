@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div class="h-full w-hull bg-gradient-to-bl from-teal-400 to-purple-500 flex items-center justify-center">
        <div class="w-full md:w-1/2 lg:w-1/3 flex items-center flex-col justify-center">
            <span class="rounded-full text-6xl w-24 h-24 flex items-center justify-center bg-white shadow text-red-500"><i class="las la-user-shield"></i></span>
            <h1 class="text-white text-5xl font-bold">{!! $title ?? __( 'Not Allowed Action' ) !!}</h1>
            <p class="py-3 font-semibold text-white text-lg text-center">{{ $message }}</p>
            <div class="flex -mx-4 my-4 flex-wrap">
                <div class="px-4"><a class="btn bg-white text-gray-700" href="{{ ! empty( request()->query( 'back' ) ) ? urldecode( request()->query( 'back' ) ) : url()->previous() }}"><i class="las la-angle-left"></i> {{ __( 'Go Back' ) }}</a></div>
                <div class="px-4"><a class="btn bg-white text-gray-700" href="{{ url()->current() . '?back=' . urlencode( request()->query( 'back' ) ?? url()->previous() )  }}"><i class="las la-sync"></i> {{ __( 'Try Again' ) }}</a></div>
                <div class="px-4"><a class="btn bg-white text-gray-700" href="{{ url( '/' ) }}"><i class="las la-home"></i> {{ __( 'Home' ) }}</a></div>
            </div>
        </div>
    </div>
@endsection