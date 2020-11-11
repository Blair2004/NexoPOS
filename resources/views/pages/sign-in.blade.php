<?php

use App\Classes\Hook;
use App\Classes\Response;
?>
@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full flex items-center overflow-y-auto pb-10 bg-gray-300">
        <div class="container mx-auto p-4 md:p-0 flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-3/5 lg:w-2/5">
                <div class="flex justify-center items-center py-6">
                    <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
                </div>
                {!! Hook::filter( 'ns.before-login-fields', new Response ) !!}
                <ns-login>
                    <div class="w-full flex items-center justify-center">
                        <h3 class="font-hairline text-sm">{{ __( 'Loading...' ) }}</h3>
                    </div>
                </ns-login>
                {!! Hook::filter( 'ns.after-login-fields', new Response ) !!}
            </div>
        </div>
    </div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/auth.js' ) }}"></script>
@endsection