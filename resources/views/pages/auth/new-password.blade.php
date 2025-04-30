<?php
use App\Classes\Hook;
use App\Classes\Output;
use App\Events\RenderBeforeNewPasswordFormEvent;
use App\Events\RenderAfterNewPasswordFormEvent;
use App\Events\RenderNewPasswordFooterEvent;
?>

@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full flex items-center overflow-y-auto pb-10 bg-gray-300">
        <div class="container mx-auto p-4 md:p-0 flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-3/5 lg:w-2/5">
                <div class="flex justify-center items-center py-6">
                    <img class="w-32" src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" alt="NexoPOS">
                </div>
                {!! Output::dispatch( RenderBeforeNewPasswordFormEvent::class ) !!}
                @include( '/common/auth/new-password-form' )
                {!! Output::dispatch( RenderAfterNewPasswordFormEvent::class ) !!}
            </div>
        </div>
    </div>
@endsection

@section( 'layout.base.footer' )
    @parent
    {!! Output::dispatch( RenderNewPasswordFooterEvent::class ) !!}
    @vite([ 'resources/ts/auth.ts' ])
@endsection