import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * 顧客表示の受信 (Laravel Echo + Pusher)。旧と同じく Pusher を使うが、チャンネルは
 * 店舗ごとの private channel (認可は /broadcasting/auth、表示端末のログインが要る)。
 * - Pusher の鍵 (VITE_PUSHER_APP_KEY) が無いときは Echo を作らず null (画面は表示だけ、JS は止めない)。
 * - Echo / Pusher の読み込みに失敗したら false (呼び出し側がスタッフ向けの案内を出す)。
 * - options.authorize を渡すと、チャンネル認可をその関数で行う (displayConnection.js)。
 */
export async function setupEcho(env = import.meta.env, options = {}) {
    const key = env.VITE_PUSHER_APP_KEY;
    if (!key) {
        console.warn('顧客表示: VITE_PUSHER_APP_KEY が未設定のため受信しません');
        return null;
    }
    let Echo, Pusher;
    try {
        [{ default: Echo }, { default: Pusher }] = await Promise.all([
            import('laravel-echo'),
            import('pusher-js'),
        ]);
    } catch (error) {
        console.warn('顧客表示: Echo / Pusher を読み込めませんでした', error);
        return false;
    }
    window.Pusher = Pusher;
    const echoOptions = {
        broadcaster: 'pusher',
        key: key,
        cluster: env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: true,
    };
    if (options.authorize) {
        echoOptions.channelAuthorization = { customHandler: options.authorize };
    }
    window.Echo = new Echo(echoOptions);
    return window.Echo;
}
