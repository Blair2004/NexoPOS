<?php

use App\Classes\Hook;
use App\Services\DateService;
use App\Services\Helper;

?>
<!DOCTYPE html>
<html lang="en" class="{{ Auth::user()->attribute->theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    <link rel="stylesheet" href="{{ loadcss( 'app.css' ) }}">
    <link rel="stylesheet" href="{{ asset( 'css/line-awesome.css' ) }}">
    <link rel="stylesheet" href="{{ loadcss( $theme . '.css' ) }}">
    @yield( 'layout.default.header' )
</head>
<body>
    @yield( 'layout.default.body' )
        @section( 'layout.default.footer' )
    @show
</body>
</html>