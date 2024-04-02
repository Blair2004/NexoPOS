<?php

use App\Classes\Hook;
use App\Classes\Output;
?>
<?php
    $output     =   new Output;
    Hook::action( 'ns-dashboard-before-title', $output, $identifier ?? null );
    echo ( string ) $output;
?>

<div class="page-inner-header mb-4">
    <h3 class="text-3xl text-primary font-bold">{!! $title ?? __( 'Unnamed Page' ) !!}</h3>
    <p class="text-secondary">{{ $description ?? __( 'No description' ) }}</p>
</div>
@include( 'components.session-message' )

<?php
    $output     =   new Output;
    Hook::action( 'ns-dashboard-after-title', $output, $identifier ?? null );
    echo ( string ) $output;
?>
