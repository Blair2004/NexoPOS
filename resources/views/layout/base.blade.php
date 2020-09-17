<?php
use App\Services\DateService;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    <link rel="stylesheet" href="{{ asset( 'css/app.css' ) }}">
    @yield( 'layout.base.header' )
    <script>
        /**
         * constant where is registered
         * global custom components
         * @param {Object}
         */
        const nsExtraComponents     =   new Object;

        /**
         * describe a global NexoPOS object
         * @param {object} ns
         */
        const ns =   { nsExtraComponents };

        /**
         * store the server date
         * @param {string}
         */
        ns.date                     =   {
            current : '{{ app()->make( DateService::class )->toDateTimeString() }}',
        }
    </script>
</head>
<body>
    @yield( 'layout.base.body' )
    @section( 'layout.base.footer' )
        @include( '../common/footer' )
    @show
</body>
</html>