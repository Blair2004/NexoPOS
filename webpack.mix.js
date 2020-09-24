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

mix.extend( 'vue', new class {
    webpackRules() {
        return [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    loaders: {
                        // Since sass-loader (weirdly) has SCSS as its default parse mode, we map
                        // the "scss" and "sass" values for the lang attribute to the right configs here.
                        // other preprocessors should work out of the box, no loader config like this necessary.
                        'scss': 'vue-style-loader!css-loader!sass-loader',
                        'sass': 'vue-style-loader!css-loader!sass-loader?indentedSyntax',
                    }
                    // other vue-loader options go here
                }
            }, {
                test: /\.tsx?$/,
                loader: 'ts-loader',
                exclude: /node_modules/,
                options: {
                    appendTsSuffixTo: [/\.vue$/],
                }
            },
        ]
    }

    webpackConfig( webpackConfig ) {
        webpackConfig.resolve.extensions( '.vue' );
    }
});
mix.disableNotifications();
mix.sourceMaps();
mix
    .js('resources/js/bootstrap.js', 'public/js')
    .js('resources/js/app.js', 'public/js')
    .js('resources/js/pos-init.js', 'public/js')
    .js('resources/js/pos.js', 'public/js')
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