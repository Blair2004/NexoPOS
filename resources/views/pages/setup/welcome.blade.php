@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="nexopos-setup" class="h-full w-full bg-gray-300 flex">
    <div class="container mx-auto flex-auto items-center justify-center flex">
        <div id="sign-in-box" class="w-full md:w-1/3">
            <div class="bg-white rounded shadow -my-2">
                <div class="welcome-box h-56 border-b border-gray-300 p-2">
                    Hello World
                </div>
                <div class="bg-gray-200 p-2 flex justify-between">
                    <ns-button type="primary">Hello</ns-button>
                </div>
            </div>
        </div>
    </div>    
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/setup.js' ) }}"></script>
@endsection