@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="nexopos-setup" class="h-full w-full items-center overflow-y-auto pb-10 bg-gray-300">
    <div class="container mx-auto p-4 md:p-0 flex-auto items-center justify-center flex">
        <div id="setup" class="w-full md:w-3/5 lg:w-2/5">
            <div class="flex flex-shrink-0 justify-center items-center py-6">
                <img class="w-32" src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" alt="NexoPOS">
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