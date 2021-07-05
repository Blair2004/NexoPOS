const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');
const path = require( 'path' );

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
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    loader: "ts-loader",
                    exclude: /node_modules/
                },{
                    test: /\.m?js$/,
                    exclude: /(node_modules|bower_components)/,
                    use: {
                      loader: 'babel-loader',
                      options: {
                        presets: ['@babel/preset-env'],
                      },
                    },
                },
            ]
        },
        resolve: {
            extensions: [ "*", ".js", ".jsx", ".vue", ".ts", ".tsx"],
            alias: {
                '@': path.resolve( __dirname, 'resources/ts/')
            }
        }
    });


mix.disableNotifications();
mix.sourceMaps();
mix
    .js( 'resources/ts/bootstrap.ts', 'public/js')
    .js( 'resources/ts/lang-loader.ts', 'public/js')
    .js( 'resources/ts/app.ts', 'public/js')
    .js( 'resources/ts/dashboard.ts', 'public/js')
    .js( 'resources/ts/cashier.ts', 'public/js')
    .js( 'resources/ts/update.ts', 'public/js')
    .js( 'resources/ts/pos-init.ts', 'public/js')
    .js( 'resources/ts/pos.ts', 'public/js')
    .js( 'resources/ts/auth.ts', 'public/js')
    .js( 'resources/ts/setup.ts', 'public/js')
    .js( 'resources/ts/popups.ts', 'public/js/' )
    .extract([ 
        // 'vue', 
        // 'lodash', 
        // 'vue-apexcharts',
        // 'chart.js', 
        // 'axios', 
        // 'moment', 
        // 'rxjs', 
        // 'rx', 
        // 'vue-router', 
        // 'dayjs',
        // 'vue-html-to-paper',
        // '@wordpress/hooks',
        // 'numeral',
        // 'css-loader',
        // 'autoprefixer',
        // 'apexcharts',
        // '@ckeditor/ckeditor5-vue',
        // 'twitter_cldr',
        // 'vue-upload-component'
    ])
    .sass('resources/sass/app.scss', 'public/css')
    .options({
        processCssUrls: false,
        postCss: [ tailwindcss('./tailwind.config.js') ],
    })