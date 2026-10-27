/**
 * クライアントエラー収集ユーティリティ
 * - グローバルエラーハンドラー
 * - Promise未処理エラー
 * - console.error/warn のラップ
 */

class ErrorLogger {
    constructor() {
        this.queue = [];
        this.isSending = false;
        this.debounceTimer = null;
        this.initialized = false;
    }

    init() {
        if (this.initialized) return;
        this.initialized = true;

        // グローバルエラーハンドラー
        window.onerror = (message, source, lineno, colno, error) => {
            this.log('error', {
                message: message,
                stack: error?.stack || `at ${source}:${lineno}:${colno}`,
            });
            return false; // デフォルトのエラー処理も実行
        };

        // Promise未処理エラー
        window.addEventListener('unhandledrejection', (event) => {
            const error = event.reason;
            this.log('unhandled', {
                message: error?.message || String(error),
                stack: error?.stack || null,
            });
        });

        // console.error をラップ
        const originalError = console.error;
        console.error = (...args) => {
            originalError.apply(console, args);
            this.log('error', {
                message: args.map(arg =>
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                ).join(' '),
            });
        };

        // console.warn をラップ
        const originalWarn = console.warn;
        console.warn = (...args) => {
            originalWarn.apply(console, args);
            this.log('warn', {
                message: args.map(arg =>
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                ).join(' '),
            });
        };

        console.log('[ErrorLogger] 初期化完了');
    }

    log(errorType, data) {
        // 自分自身のエラーは無視（無限ループ防止）
        if (data.message && data.message.includes('/api/client-error')) {
            return;
        }

        this.queue.push({
            error_type: errorType,
            message: data.message,
            stack: data.stack || null,
            url: window.location.href,
            extra_data: data.extra || null,
            timestamp: new Date().toISOString(),
        });

        // デバウンス（500ms後に送信）
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.flush(), 500);
    }

    async flush() {
        if (this.isSending || this.queue.length === 0) return;

        this.isSending = true;
        const errors = [...this.queue];
        this.queue = [];

        try {
            // 複数エラーをまとめて送信
            for (const error of errors) {
                await fetch('/api/client-error', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(error),
                });
            }
        } catch (e) {
            // 送信失敗は無視（コンソールには出さない、無限ループ防止）
        } finally {
            this.isSending = false;
        }
    }

    // 手動でエラーを記録
    capture(message, extra = null) {
        this.log('error', { message, extra });
    }
}

// シングルトンインスタンス
const errorLogger = new ErrorLogger();

export default errorLogger;
