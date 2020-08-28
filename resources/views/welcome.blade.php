@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto flex-col items-center justify-center flex">
            <div class="flex justify-center items-center py-6">
                <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
            </div>
            <p class="text-gray-700 text-center w-1/3 mb-8">{{ __( 'If you see this page, this means NexoPOS 4.x is correctly installed on your system. 
                As this page is mean to be the frontend, NexoPOS 4.x doesn\'t have a frontend for the meantim. 
                This page shows useful link that will takes you to the dashboard.' ) }}</p>
            <div class="flex -mx-4 rounded-lg shadow p-2 bg-white">
                <p class="px-4"><a class="text-blue-600 text-sm" href="{{ url( '/dashboard' ) }}">{{ __( 'Dashboard' ) }}</a></p>
                <p class="px-4"><a class="text-blue-600 text-sm" href="{{ route( 'ns.login' ) }}">{{ __( 'Sign In' ) }}</a></p>
                <p class="px-4"><a class="text-blue-600 text-sm" href="{{ route( 'ns.register' ) }}">{{ __( 'Sign Up' ) }}</a></p>
            </div>
        </div>
    </div>
@endsection