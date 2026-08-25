<?php

namespace Tests\Unit;

use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTestBootstrapTest extends TestCase
{
    public function test_bootstrap_pins_application_base_path_when_a_module_autoloader_is_prepended(): void
    {
        $applicationBasePath = realpath( dirname( __DIR__, 2 ) );

        $this->assertNotFalse( $applicationBasePath );
        $this->assertSame( $applicationBasePath, $_ENV['APP_BASE_PATH'] ?? null );
        $this->assertSame( $applicationBasePath, $_SERVER['APP_BASE_PATH'] ?? null );
        $this->assertSame( $applicationBasePath, getenv( 'APP_BASE_PATH' ) );

        $moduleVendorDirectory = $applicationBasePath . '/modules/SyntheticModule/vendor';
        $moduleLoader = new ClassLoader( $moduleVendorDirectory );
        $moduleLoader->register( prepend: true );

        try {
            $this->assertSame(
                $moduleVendorDirectory,
                array_key_first( ClassLoader::getRegisteredLoaders() )
            );
            $this->assertSame( $applicationBasePath, Application::inferBasePath() );
        } finally {
            $moduleLoader->unregister();
        }
    }
}
