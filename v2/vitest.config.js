import { defineConfig } from 'vitest/config';

// JS の単体テスト (npm run test:js)。Vite の laravel プラグインは使わない
export default defineConfig({
    test: {
        include: ['tests/js/**/*.test.js'],
    },
});
