<?php

declare(strict_types=1);

$applicationBasePath = realpath( dirname( __DIR__ ) );

if ( $applicationBasePath === false ||
    ! is_file( $applicationBasePath . '/bootstrap/app.php' ) ||
    ! is_file( $applicationBasePath . '/vendor/autoload.php' ) ) {
    throw new RuntimeException( 'Unable to locate the NexoPOS application root.' );
}

$_ENV['APP_BASE_PATH'] = $applicationBasePath;
$_SERVER['APP_BASE_PATH'] = $applicationBasePath;

putenv( "APP_BASE_PATH={$applicationBasePath}" );

require_once $applicationBasePath . '/vendor/autoload.php';
