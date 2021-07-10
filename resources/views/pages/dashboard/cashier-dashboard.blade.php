<?php

use App\Services\ModulesService;
$module     =   app()->make( ModulesService::class );
?>
<div id="dashboard-content" class="px-4">
    <ns-cashier-dashboard :show-commission="{{ $module->getIfEnabled( 'NsCommissions' ) ? 'true' : 'false' }}"></ns-cashier-dashboard>
</div>