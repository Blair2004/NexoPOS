<?php

use App\Classes\Output;
use App\Events\RenderHeaderEvent;

echo Output::dispatch( 
    RenderHeaderEvent::class, 
    request()->route()->getName() 
);