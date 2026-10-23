/**
 * 顧客表示の端末とサーバーのつながりを保つ。
 * - キープアライブ: 10 分ごとに GET /display/ping。セッションを新しく保ち、切れていれば remember cookie で入り直す
 *   (応答で XSRF-TOKEN cookie も新しくなる)。
 * - チャンネル認可 (Pusher の customHandler): POST /broadcasting/auth を axios で送る
 *   (CSRF は axios が XSRF-TOKEN cookie から付けるので、ページを開いたあとにセッションが作り直されても通る)。
 *   419 (セッション切れ) は ping で入り直して 1 回だけやり直す。
 * - ログインが切れた (401/403) ときは黙って止まらず onLoginRequired でスタッフ向けの案内に切り替える。
 */
export const KEEPALIVE_MS = 10 * 60 * 1000;

const statusOf = (error) => error?.response?.status ?? error?.status;

export function createDisplayConnection({ axios, onLoginRequired }) {
    let loginRequired = false;
    const requireLogin = (reason) => {
        console.warn('顧客表示: ログインが必要です (' + reason + ')');
        if (loginRequired) return;
        loginRequired = true;
        onLoginRequired(reason);
    };

    async function ping() {
        try {
            await axios.get('/display/ping');
            return true;
        } catch (error) {
            if (statusOf(error) === 401) {
                requireLogin('keep-alive 401');
            } else {
                console.warn('顧客表示: キープアライブに失敗しました', error);
            }
            return false;
        }
    }

    async function authorize({ socketId, channelName }, callback) {
        const post = () => axios.post('/broadcasting/auth', { socket_id: socketId, channel_name: channelName });
        let error;
        try {
            const res = await post();
            callback(null, res.data);
            return;
        } catch (e) {
            error = e;
        }
        if (statusOf(error) === 419 && await ping()) {
            try {
                const res = await post();
                callback(null, res.data);
                return;
            } catch (e) {
                error = e;
            }
        }
        const status = statusOf(error);
        if (status === 401 || status === 403 || status === 419) {
            requireLogin('channel auth ' + status);
        }
        callback(error instanceof Error ? error : new Error(String(error)), null);
    }

    function startKeepAlive(setIntervalFn = setInterval) {
        return setIntervalFn(ping, KEEPALIVE_MS);
    }

    return { ping, authorize, startKeepAlive };
}
