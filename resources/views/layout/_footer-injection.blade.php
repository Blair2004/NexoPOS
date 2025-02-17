<?php

use App\Classes\Output;
use App\Events\RenderFooterEvent;

$output     =   new Output;

RenderFooterEvent::dispatch( 
    $output, 
    request()->route()->getName()
);

echo $output;