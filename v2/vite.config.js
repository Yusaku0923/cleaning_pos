import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
                // 旧 (vue-loader 15) と同じく要素の間の空白を残す (Vue 3 の既定 'condense' はボタンの間の隙間を消す)
                compilerOptions: { whitespace: 'preserve' },
            },
        }),
    ],
    resolve: {
        alias: [
            // Blade 内の DOM をそのまま Vue のテンプレートにする (旧と同じく #app 全体に mount) ため実行時コンパイラ入りを使う
            { find: /^vue$/, replacement: 'vue/dist/vue.esm-bundler.js' },
            // 旧 SCSS (Laravel Mix) の `@import '~bootstrap/...'` をそのまま使う
            { find: /^~(.*)$/, replacement: '$1' },
        ],
    },
    css: {
        preprocessorOptions: {
            // 旧と同じ sass 1.55 (legacy API) で同じ CSS を出す
            scss: { api: 'legacy', quietDeps: true },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
