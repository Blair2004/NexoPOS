<?php

use App\Classes\Output;
use App\Events\RenderFooterEvent;
use Illuminate\Routing\Route;

if ( request()->route() instanceof Route ) {
    echo Output::dispatch( 
        RenderFooterEvent::class, 
        request()->route()->getName() 
    );
}