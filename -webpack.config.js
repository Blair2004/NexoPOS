const path = require('path')
const UglifyJsPlugin = require('uglifyjs-webpack-plugin')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin')
const VueLoaderPlugin = require('vue-loader/lib/plugin')
const autoprefixer = require('autoprefixer');
const webpack = require('webpack');


let env = process.env.NODE_ENV
let isDev = env === 'development'

const WEBPACK_CONFIG = {
    mode: env,
    entry: {
        app: ['./resources/js/app.ts', './resources/sass/app.scss'],
    },
    output: {
        publicPath: './public',
        path: path.resolve(__dirname, 'public'),
        filename: 'js/[name].js',
        chunkFilename: 'js/chunks/app.js'

    },
    module: {
        rules: [{
                test: /\.tsx?$/,
                loader: 'ts-loader',
                options: { appendTsSuffixTo: [/\.vue$/] },
                exclude: /node_modules/,
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader',
                    'sass-loader'
                ],
                exclude: /node_modules/,
            },
            {
                test: /\.(ttf|eot|svg|woff|woff2)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]',
                    outputPath: '/fonts',
                    publicPath: '/fonts'
                }
            }
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/[name].css'
        }),
        new VueLoaderPlugin(),
        new webpack.LoaderOptionsPlugin({
            options: {
                postcss: [
                    autoprefixer()
                ]
            }
        })

    ],
    resolve: {
        extensions: ['.js', '.jsx', '.vue', '.ts', '.tsx'],
        alias: {
            vue$: 'vue/dist/vue.esm.js',
        },
    },
    optimization: {
        splitChunks: {
            chunks: 'async',
            minSize: 30000,
            maxSize: 0,
            minChunks: 1,
            maxAsyncRequests: 5,
            maxInitialRequests: 3,
            automaticNameDelimiter: '~',
            name: true,
            cacheGroups: {
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    priority: -10
                },
                default: {
                    minChunks: 2,
                    priority: -20,
                    reuseExistingChunk: true
                }
            }
        }
    }


}

if (!isDev) {
    WEBPACK_CONFIG.optimization = {
        minimizer: [
            new UglifyJsPlugin({
                cache: true,
                parallel: true,
                sourceMap: true
            }),
            new OptimizeCSSAssetsPlugin({})
        ]
    }

    WEBPACK_CONFIG.plugins.push(
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        })

    )
}

module.exports = WEBPACK_CONFIG