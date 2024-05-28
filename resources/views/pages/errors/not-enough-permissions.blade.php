@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div class="h-full w-full overflow-y-auto pb-10 bg-gradient-to-bl from-orange-500 to-red-500 flex items-center justify-center">
        <div class="w-full md:w-1/2 lg:w-1/3 flex items-center flex-col justify-center">
            <span class="rounded-full text-6xl w-24 h-24 flex items-center justify-center bg-white shadow text-red-500 mb-4"><i class="las la-user-shield"></i></span>
            <h1 class="text-white text-center text-4xl font-bold">{!! $title ?? __( 'Access Denied' ) !!}</h1>
            <p class="py-3 text-white text-center text-lg">{!! $message !!}</p>
            <div class="flex md:w-1/2 w-full justify-around">
                <div class="justify-center flex-wrap w-full">
                    <div class="px-2 mb-2">
                        <div class="ns-button hover-info">
                            <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ route( 'ns.logout' ) }}"><i class="las la-sign-out-alt"></i> {{ __( 'Log out' ) }}</a>
                        </div>
                    </div>
                    <div class="px-2 mb-2">
                        <div class="ns-button hover-info">
                            <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ $back }}"><i class="las la-angle-left"></i> {{ __( 'Go Back' ) }}</a>
                        </div>
                    </div>
                </div>
                <div class="justify-center flex-wrap w-full"> 
                    <div class="px-2 mb-2">
                        <div class="ns-button hover-info">
                            <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url()->current() . '?back=' . urlencode( request()->query( 'back' ) ?? url()->previous() )  }}"><i class="las la-sync"></i> {{ __( 'Retry' ) }}</a>
                        </div>
                    </div>
                    <div class="px-2 mb-2">
                        <div class="ns-button hover-info">
                            <a class="shadow px-2 py-1 rounded block w-full lg:w-auto" href="{{ url( '/' ) }}"><i class="las la-home"></i> {{ __( 'Home' ) }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection