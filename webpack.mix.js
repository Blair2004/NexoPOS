const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

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

mix.disableNotifications();
mix.sourceMaps();
mix
    .js('resources/js/bootstrap.js', 'public/js')
    .js('resources/js/app.js', 'public/js')
    .js('resources/js/auth.js', 'public/js')
    .js('resources/js/setup.js', 'public/js')
    .extract([ 
        'vue', 
        'lodash', 
        'chart.js', 
        'axios', 
        'moment', 
        'rxjs', 
        'rx', 
        'vue-router', 
        'dayjs' 
    ])
    .sass('resources/sass/app.scss', 'public/css')
    .options({
        processCssUrls: false,
        postCss: [ tailwindcss('./tailwind.config.js') ],
    })