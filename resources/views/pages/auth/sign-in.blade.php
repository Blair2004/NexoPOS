<?php

use App\Classes\Hook;
use App\Classes\Output;
use App\Events\AfterLoginFieldsEvent;
use App\Events\BeforeLoginFieldsEvent;
use App\Events\RenderLoginFooterEvent;
?>
@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full flex items-center overflow-y-auto pb-10">
        <div class="container mx-auto p-4 md:p-0 flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-3/5 lg:w-2/5 xl:w-84">
                <div class="flex justify-center items-center py-6">
                    @if ( ! ns()->option->get( 'ns_store_square_logo', false ) )
                    <img class="w-32" src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" alt="NexoPOS">
                    @else
                    <img src="{{ ns()->option->get( 'ns_store_square_logo' ) }}" alt="NexoPOS">
                    @endif
                </div>
                <x-session-message></x-session-message>
                {!! Output::dispatch( BeforeLoginFieldsEvent::class ) !!}
                @include( '/common/auth/sign-in-form' )
                {!! Output::dispatch( AfterLoginFieldsEvent::class ) !!}
            </div>
        </div>
    </div>
@endsection

@section( 'layout.base.footer' )
    @parent
    {!! Output::dispatch( RenderLoginFooterEvent::class ) !!}
    @vite([ 'resources/ts/auth.ts' ])
@endsection