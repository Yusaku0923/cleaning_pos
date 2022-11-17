const mix = require('laravel-mix');

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

 mix.js('resources/js/app.js', 'public/js')
 .vue()
 .sass('resources/sass/app.scss', 'public/css')
 .sass('resources/sass/modal.scss', 'public/css')
 .sass('resources/sass/daily_report.scss', 'public/css')
 .sass('resources/sass/return.scss', 'public/css')
 .sass('resources/sass/invoice.scss', 'public/css');
