<?php

use App\Classes\Hook;
use App\Classes\Output;
use App\Events\AfterDashboardTitleEvent;
use App\Events\BeforeDashboardTitleEvent;

?>
<?php
    $output     =   new Output;
    BeforeDashboardTitleEvent::dispatch( $output );
    echo $output;
?>

<div class="page-inner-header mb-4">
    <h3 class="text-3xl text-primary font-bold">{!! $title ?? __( 'Unnamed Page' ) !!}</h3>
    <p class="text-secondary">{{ $description ?? __( 'No description' ) }}</p>
</div>
@include( 'components.session-message' )

<?php
    $output     =   new Output;
    AfterDashboardTitleEvent::dispatch( $output );
    echo $output;
?>
