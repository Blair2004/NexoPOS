<?php

use App\Classes\Hook;
use App\Services\DateService;
use App\Services\Helper;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    <link rel="stylesheet" href="{{ asset( 'css/app.css' ) }}">
    @yield( 'layout.default.header' )
</head>
<body>
    @yield( 'layout.default.body' )
        @section( 'layout.default.footer' )
    @show
</body>
</html>