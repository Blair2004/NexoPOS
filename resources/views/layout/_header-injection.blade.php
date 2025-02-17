<?php

use App\Classes\Output;
use App\Events\RenderHeaderEvent;

$output     =   new Output;

RenderHeaderEvent::dispatch( 
    $output, 
    request()->route()->getName()
);

echo $output;