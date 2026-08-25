<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexoposVueRuntimePluginTest extends TestCase
{
    #[Test]
    public function vue_runtime_entry_is_registered_in_core_vite_config(): void
    {
        $viteConfig = file_get_contents( base_path( 'vite.config.js' ) );

        $this->assertNotFalse( $viteConfig );
        $this->assertStringContainsString( 'resources/ts/vue-runtime.ts', $viteConfig );
    }

    #[Test]
    public function layouts_load_vue_runtime_before_other_scripts(): void
    {
        $layouts = [
            resource_path( 'views/layout/dashboard.blade.php' ),
            resource_path( 'views/layout/dashboard-blank.blade.php' ),
            resource_path( 'views/layout/base.blade.php' ),
            resource_path( 'views/layout/default.blade.php' ),
        ];

        foreach ( $layouts as $layout ) {
            $this->assertFileExists( $layout );
            $contents = file_get_contents( $layout );
            $this->assertStringContainsString(
                'resources/ts/vue-runtime.ts',
                $contents,
                "Expected {$layout} to load the shared Vue runtime."
            );
        }
    }

    #[Test]
    public function vue_runtime_source_exposes_shared_helpers(): void
    {
        $source = file_get_contents( resource_path( 'ts/vue-runtime.ts' ) );

        $this->assertNotFalse( $source );
        $this->assertStringContainsString( 'window.NexoPOSVue', $source );
        $this->assertStringContainsString( 'window.ns.vue', $source );
        $this->assertStringContainsString( 'function nsCreateApp', $source );
        $this->assertStringContainsString( 'function nsRegisterComponent', $source );
        $this->assertStringContainsString( 'window.nsCreateApp', $source );
        $this->assertStringContainsString( 'window.nsRegisterComponent', $source );
    }

    #[Test]
    public function nexopos_vue_plugin_rewrites_vue_imports_to_shared_runtime(): void
    {
        $plugin = file_get_contents( resource_path( 'vite-plugin-nexopos-vue.js' ) );

        $this->assertNotFalse( $plugin );
        $this->assertStringContainsString( 'export function nexoposVueRuntime', $plugin );
        $this->assertStringContainsString( 'source === "vue"', $plugin );
        $this->assertStringContainsString( 'globalThis.ns?.vue ?? globalThis.NexoPOSVue', $plugin );
        $this->assertStringContainsString( 'export function nsCreateApp', $plugin );
        $this->assertStringContainsString( 'export function nsRegisterComponent', $plugin );
    }

    #[Test]
    public function module_vite_factory_enables_shared_runtime_plugin(): void
    {
        $factory = file_get_contents( resource_path( 'vite-nexopos-module.js' ) );

        $this->assertNotFalse( $factory );
        $this->assertStringContainsString( 'export function defineNexoPOSModuleConfig', $factory );
        $this->assertStringContainsString( 'nexoposVueRuntime()', $factory );
        $this->assertStringContainsString( 'outDir: "Public/build"', $factory );
    }

    #[Test]
    public function reference_modules_use_shared_module_vite_config(): void
    {
        $appointmentVite = file_get_contents( base_path( 'modules/NsAppointments/vite.config.js' ) );
        $cloudVite = file_get_contents( base_path( 'modules/CloudDeployer/vite.config.js' ) );

        $this->assertNotFalse( $appointmentVite );
        $this->assertNotFalse( $cloudVite );
        $this->assertStringContainsString( 'defineNexoPOSModuleConfig', $appointmentVite );
        $this->assertStringContainsString( 'defineNexoPOSModuleConfig', $cloudVite );
    }
}
