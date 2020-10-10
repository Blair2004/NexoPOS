@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container flex-auto flex-col items-center justify-center flex m-4 sm:mx-auto">
            <div class="flex justify-center items-center py-6">
                <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
            </div>
            <div class="rounded shadow bg-white w-full md:w-1/2 lg:w-1/3 overflow-hidden">
                <div id="section-header" class="p-4">
                    <p class="text-gray-700 text-center b-8 text-sm">{{ __( 'If you see this page, this means NexoPOS 4.x is correctly installed on your system. 
                        As this page is mean to be the frontend, NexoPOS 4.x doesn\'t have a frontend for the meantime. 
                        This page shows useful link that will takes you to the important resources.' ) }}</p>
                </div>
                <div class="flex shadow bg-gray-100 border-t">
                    <div class="flex w-1/3"><a class="text-blue-600 text-sm w-full py-2 text-center hover:bg-gray-200" href="{{ url( '/dashboard' ) }}">{{ __( 'Dashboard' ) }}</a></div>
                    <div class="flex w-1/3"><a class="text-blue-600 text-sm w-full py-2 text-center hover:bg-gray-200" href="{{ route( 'ns.login' ) }}">{{ __( 'Sign In' ) }}</a></div>
                    <div class="flex w-1/3"><a class="text-blue-600 text-sm w-full py-2 text-center hover:bg-gray-200" href="{{ route( 'ns.register' ) }}">{{ __( 'Sign Up' ) }}</a></div>
                </div>
            </div>
        </div>
    </div>
@endsection