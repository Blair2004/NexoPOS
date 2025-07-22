<?php

use App\Classes\Output;
use App\Events\RenderHeaderEvent;
use Illuminate\Routing\Route;

if ( request()->route() instanceof Route ) {
    echo Output::dispatch( 
        RenderHeaderEvent::class, 
        request()->route()->getName() 
    );
}