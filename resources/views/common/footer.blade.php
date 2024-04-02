<?php

use App\Services\Options;
use App\Classes\Output;
use App\Classes\Hook;
use Illuminate\Support\Facades\Cookie;

$options            =   app()->make( Options::class );

$authentication     =   [
    'token'         =>  Cookie::get( 'ns_token' ),
    'csrf'          =>  csrf_token()
];

$json               =   [
    'ns_currency_symbol'                =>  $options->get( 'ns_currency_symbol', '$' ),
    'ns_currency_iso'                   =>  $options->get( 'ns_currency_iso', 'USD' ),
    'ns_currency_position'              =>  $options->get( 'ns_currency_position', 'before' ),
    'ns_currency_thousand_separator'    =>  $options->get( 'ns_currency_thousand_separator', ',' ),
    'ns_currency_decimal_separator'     =>  $options->get( 'ns_currency_decimal_separator', '.' ),
    'ns_currency_precision'             =>  $options->get( 'ns_currency_precision', '0' ),
    'ns_currency_prefered'              =>  $options->get( 'ns_currency_prefered', 'iso' ),
];
?>
<script type="text/javascript">
ns.currency         =   <?php echo json_encode( $json );?>;
ns.authentication   =   <?php echo json_encode( $authentication );?>;
ns.base_url         =   '{{ url( "/" ) }}';
</script>
@vite([ 'resources/ts/bootstrap.ts' ])
<?php 
    $output     =   new Output;
    Hook::action( 'ns-footer', $output );
    echo ( string ) $output;
?>
@yield( 'layout.dashboard.footer.inject' )