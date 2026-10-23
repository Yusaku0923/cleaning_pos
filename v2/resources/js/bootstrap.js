import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * 顧客表示の受信 (Laravel Echo + Pusher)。旧と同じく Pusher を使うが、チャンネルは
 * 店舗ごとの private channel (認可は /broadcasting/auth、表示端末のログインが要る)。
 * Pusher の鍵 (VITE_PUSHER_APP_KEY) が無いときは Echo を作らない (画面は表示だけ、JS は止めない)。
 */
export async function setupEcho(env = import.meta.env) {
    const key = env.VITE_PUSHER_APP_KEY;
    if (!key) {
        console.warn('顧客表示: VITE_PUSHER_APP_KEY が未設定のため受信しません');
        return null;
    }
    const [{ default: Echo }, { default: Pusher }] = await Promise.all([
        import('laravel-echo'),
        import('pusher-js'),
    ]);
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: key,
        cluster: env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: true,
    });
    return window.Echo;
}
