const mix                   = require('laravel-mix');
const tailwindcss           = require('tailwindcss');
const path                  = require( 'path' );
// const { VueLoaderPlugin } = require("vue-loader");

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

// mix.disableNotifications();

mix
    .ts( 'resources/ts/app.ts', 'public/js')
    .ts( 'resources/ts/dashboard.ts', 'public/js')
    .ts( 'resources/ts/update.ts', 'public/js')
    .ts( 'resources/ts/pos-init.ts', 'public/js')
    .ts( 'resources/ts/pos.ts', 'public/js')
    .ts( 'resources/ts/auth.ts', 'public/js')
    .ts( 'resources/ts/setup.ts', 'public/js')
    .ts( 'resources/ts/popups.ts', 'public/js/' )
    .ts( 'resources/ts/bootstrap.ts', 'public/js')
    .vue({ version: 2 })
    .sourceMaps();

mix.sass( 'resources/sass/app.scss', 'public/css' )
    .webpackConfig({
        module: {
            rules: [
                {
                    test: /\.vue$/,
                    loader: 'vue-loader',
                }, {
                    test: /\.tsx?$/,
                    loader: 'ts-loader',
                    exclude: /node_modules/,
                }, {
                    test: /\.ts$/,
                    loader: 'ts-loader',
                    options: { appendTsSuffixTo: [/\.vue$/] }
                }
            ]
        },
        resolve: {
            extensions: [ ".js", "vue", "*", ".jsx", ".ts", ".tsx"],
            alias: {
                '@'     : path.resolve(__dirname, 'resources/ts/'),
                'vue$'  : 'vue/dist/vue.esm.js',
            }
        },
        // plugins: [
        //     // new VueLoaderPlugin()
        // ]
    })
    .options({
        processCssUrls: false,
        postCss: [
            tailwindcss( './tailwind.config.js' )
        ]
    });