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

mix.extend( 'webpackCustomConfig', new class {
    webpackRules() {
        console.log( 'is effective' );
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
        webpackConfig.resolve.alias = {
            'vue$': 'vue/dist/vue.esm.js',
            '@': __dirname + '/resources/ts'
        };
    }
});
// mix.webpackCustomConfig();
mix
    .webpackConfig({
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    loader: "ts-loader",
                    exclude: /node_modules/,
                    // options: {
                    //     appendTsSuffixTo: [/\.vue$/]
                    // }
                }
            ]
        },
        resolve: {
            extensions: [ "*", ".js", ".jsx", ".vue", ".ts", ".tsx"],
            alias: {
                '@': path.resolve(__dirname, 'resources/ts/')
            }
        }
    });

mix.disableNotifications();
mix.sourceMaps();
mix
    .js('resources/ts/bootstrap.ts', 'public/js')
    .js('resources/ts/app.ts', 'public/js')
    .js('resources/ts/update.ts', 'public/js')
    .js('resources/ts/pos-init.ts', 'public/js')
    .js('resources/ts/pos.ts', 'public/js')
    .js('resources/ts/auth.ts', 'public/js')
    .js('resources/ts/setup.ts', 'public/js')
    .js( 'resources/ts/pages/dashboard/orders/ns-order-preview-popup.vue', 'public/js' )
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
    // .sass('resources/sass/app.scss', 'public/css')
    .options({
        processCssUrls: false,
        postCss: [ tailwindcss('./tailwind.config.js') ],
    })