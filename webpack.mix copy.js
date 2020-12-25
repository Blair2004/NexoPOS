const mix                   = require('laravel-mix');
const tailwindcss           = require('tailwindcss');
const path                  = require( 'path' );
var MiniCssExtractPlugin    = require('mini-css-extract-plugin');

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
                    // options: {
                    //     appendTsSuffixTo: [/\.vue$/],
                    // }
                }, {
                    test: /\.css$/,
                    use: [
                        process.env.NODE_ENV !== 'production'
                            ? 'vue-style-loader'
                            : MiniCssExtractPlugin.loader,
                        'css-loader'
                    ]
                }, {
                    test: /\.scss$/,
                    use: [
                        'vue-style-loader',
                        'css-loader',
                        {
                            loader: 'sass-loader',
                            options: {
                                // you can also read from a file, e.g. `variables.scss`
                                // use `prependData` here if sass-loader version = 8, or
                                // `data` if sass-loader version < 8
                                additionalData: `$color: red;`
                            }
                        }
                    ]
                }
            ]
        },
        resolve: {
            extensions: [ ".js", ".jsx", ".ts", ".tsx"],
            alias: {
                '@'     : path.resolve(__dirname, 'resources/ts/'),
                'vue$'  : 'vue/dist/vue.esm.js',
            }
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: 'style.css'
            })
        ]
    });

mix.disableNotifications();
mix.sourceMaps();
mix
    .ts( 'resources/ts/bootstrap.ts', 'public/js')
    .ts( 'resources/ts/app.ts', 'public/js')
    .ts( 'resources/ts/dashboard.ts', 'public/js')
    .ts( 'resources/ts/update.ts', 'public/js')
    .ts( 'resources/ts/pos-init.ts', 'public/js')
    .ts( 'resources/ts/pos.ts', 'public/js')
    .ts( 'resources/ts/auth.ts', 'public/js')
    .ts( 'resources/ts/setup.ts', 'public/js')
    .ts( 'resources/ts/popups.ts', 'public/js/' )
    .vue({ version: 2 })  
    .extract([ 
        'vue', 
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
    // .options({
    //     processCssUrls: false,
    //     postCss: [ tailwindcss('./tailwind.config.js') ],
    // })