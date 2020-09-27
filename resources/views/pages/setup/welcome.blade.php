@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="nexopos-setup" class="h-full w-full bg-gray-300 overflow-auto py-4">
    <div class="container mx-auto flex-auto items-center justify-center flex">
        <div id="sign-in-box" class="w-full md:w-3/5 lg:w-2/5 flex flex-col">
            <div class="w-full flex justify-center items-center py-4">
                <h1 class="text-6xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-teal-400 to-blue-500">NexoPOS</h1>
            </div>
            <router-view></router-view>
        </div>
    </div>    
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/setup.js' ) }}"></script>
@endsection