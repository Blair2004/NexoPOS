<?php

use App\Services\ModulesService;

/**
 * @var ModulesService $modules
 */
$modules = app()->make( ModulesService::class );

/**
 * @var \Illuminate\Console\Scheduling\Schedule $schedule
 */

/**
 * We want to make sure Modules Kernel get injected
 * on the process so that modules jobs can also be scheduled.
 */
collect( $modules->getEnabledAndAutoloadedModules() )->each( function ( $module ) use ( $schedule ) {
    $filePath = $module[ 'path' ] . 'Console' . DIRECTORY_SEPARATOR . 'Kernel.php';

    if ( is_file( $filePath ) ) {
        include_once $filePath;

        $kernelClass = 'Modules\\' . $module[ 'namespace' ] . '\Console\Kernel';

        /**
         * a kernel class should be defined
         * on the module before it's initialized.
         */
        if ( class_exists( $kernelClass ) ) {
            $object = new $kernelClass( app(), app( 'events' ) );

            if ( method_exists( $object, 'schedule' ) ) {
                $object->schedule( $schedule );
            }
        }
    }
} );
