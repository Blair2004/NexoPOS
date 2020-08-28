<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    <link rel="stylesheet" href="{{ asset( 'css/app.css' ) }}">
    @yield( 'layout.base.header' )
</head>
<body>
    @yield( 'layout.base.body' )
    @section( 'layout.base.footer' )
        @include( '../common/footer' )
    @show
</body>
</html>