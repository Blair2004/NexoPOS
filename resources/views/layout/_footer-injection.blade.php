<?php

use App\Classes\Output;
use App\Events\RenderFooterEvent;

echo Output::dispatch( 
    RenderFooterEvent::class, 
    request()->route()->getName() 
);