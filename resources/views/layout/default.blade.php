<?php
$theme  =   ns()->option->get( 'ns_default_theme', 'light' );
?>
<!DOCTYPE html>
<html lang="en" data-theme="{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    @include( 'layout._header-injection' )
    @vite([
        'resources/scss/line-awesome/1.3.0/scss/line-awesome.scss',
        'resources/css/grid.css',
        'resources/css/fonts.css',
        'resources/css/animations.css',
        'resources/css/' . $theme . '.css'
    ])
    @yield( 'layout.base.header' )
</head>
<body>
    @yield( 'layout.default.body' )
</body>
</html>
