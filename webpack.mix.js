const mix = require('laravel-mix');
require('mix-env-file');

mix.env(process.env.ENV_FILE);

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
// mix.webpackConfig({
//     devServer: {
//         host: '0.0.0.0',
//         port: 8081,
//     },
// });

mix.js('resources/js/app.js', 'public/js')
    .browserSync({
        proxy: "laravel.test",
        files: [
            './resources/**/*',
            './public/**/*',
        ],
        open: false,
        port: 3000,
        reloadOnRestart: true,
    })
    .vue()
    .sass('resources/sass/app.scss', 'public/css');
