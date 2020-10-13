@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-2/4 lg:w-1/3">
                <div class="flex justify-center items-center py-6">
                    <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
                </div>
                <ns-login>
                    <div class="w-full flex items-center justify-center">
                        <h3 class="font-thin text-sm">{{ __( 'Loading...' ) }}</h3>
                    </div>
                </ns-login>
            </div>
        </div>
    </div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/auth.js' ) }}"></script>
@endsection