const tailwindcss   = require('tailwindcss');
const path          = require( 'path' );
const mix           = require( 'laravel-mix' );

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */
mix
    .webpackConfig({
        resolve: {
            extensions: [ ".vue", ".ts" ],
            alias: {
                '@': path.resolve( __dirname, 'resources/ts/')
            }
        }
    });


mix.disableNotifications();
mix.extract();
mix.vue({ version: 2 })

if ( mix.inProduction() ) {
    mix.version();
} else {
    mix.sourceMaps();
}

mix
    .ts( 'resources/ts/bootstrap.ts', mix.inProduction() ? 'public/js/bootstrap.min' : 'public/js')
    .ts( 'resources/ts/lang-loader.ts', mix.inProduction() ? 'public/js/lang-loader.min' : 'public/js')
    .ts( 'resources/ts/app.ts', mix.inProduction() ? 'public/js/app.min' : 'public/js')
    .ts( 'resources/ts/dashboard.ts', mix.inProduction() ? 'public/js/dashboard.min' : 'public/js')
    .ts( 'resources/ts/cashier.ts', mix.inProduction() ? 'public/js/cashier.min' : 'public/js')
    .ts( 'resources/ts/update.ts', mix.inProduction() ? 'public/js/update.min' : 'public/js')
    .ts( 'resources/ts/pos-init.ts', mix.inProduction() ? 'public/js/pos-init.min' : 'public/js')
    .ts( 'resources/ts/pos.ts', mix.inProduction() ? 'public/js/pos.min' : 'public/js')
    .ts( 'resources/ts/auth.ts', mix.inProduction() ? 'public/js/auth.min' : 'public/js')
    .ts( 'resources/ts/setup.ts', mix.inProduction() ? 'public/js/setup.min' : 'public/js')
    .ts( 'resources/ts/popups.ts', mix.inProduction() ? 'public/js/popups.min' : 'public/js/' )