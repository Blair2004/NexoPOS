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
@if( env( 'BROADCAST_DRIVER' ) === 'reverb' )
<script>
document.addEventListener( 'DOMContentLoaded', () => {
    /**
     * We'll start the Echo configuration
     * from here as on the bundled file, it stores envronment details.
     */
    window.Echo = new EchoClass({
        broadcaster: 'reverb',
        key: '<?php echo env( 'REVERB_APP_KEY' );?>',
        wsHost: '<?php echo env( 'REVERB_HOST');?>',
        wsPort: '<?php echo env( 'REVERB_PORT');?>',
        wssPort: '<?php echo env( 'REVERB_PORT');?>',
        forceTLS: <?php echo ( env( 'REVERB_SCHEME' ) ?? 'https') === 'https' ? 'true' : 'false';?>,
        enabledTransports: ['ws'],
    });
});
</script>
@endif
@vite([ 'resources/ts/bootstrap.ts' ])
<?php 
    $output     =   new Output;
    Hook::action( 'ns-dashboard-footer', $output );
    echo ( string ) $output;
?>
@yield( 'layout.dashboard.footer.inject' )