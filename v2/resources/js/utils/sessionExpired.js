/**
 * ログイン (セッション) が切れたときの案内。
 * API (/api/...) が 401 / 419 を返したら、1 回だけ alert を出して POS の /login へ移る。
 * 旧は API をトークンで呼んでいたので画面を開きっぱなしでも切れなかったが、v2 はセッションの cookie なので、
 * 長く放っておくと切れる (SESSION_LIFETIME)。黙って失敗させず、入り直してもらう。
 *
 * - 顧客表示の端末 (/customer_display, /display/...) では何もしない (POS の /login に飛ばさない。
 *   表示端末は displayConnection.js がスタッフ向けの案内を出す)。
 * - ログインの画面でも何もしない (行ったり来たりしない)。
 * - 移るときは呼び出し側の catch を走らせない (「注文を登録できませんでした」などの alert が重ならないように)。
 */
export const SESSION_EXPIRED_MESSAGE = 'ログインが切れました。もう一度ログインしてください';

const statusOf = (error) => error?.response?.status;

function isApiUrl(url) {
    if (typeof url !== 'string') return false;
    try {
        // 絶対 URL でも相対 URL でも、パスが /api/ で始まるものだけ
        return new URL(url, 'http://localhost').pathname.startsWith('/api/');
    } catch (e) {
        return false;
    }
}

function isExcludedPage(pathname) {
    return pathname.startsWith('/customer_display')
        || pathname.startsWith('/display/')
        || pathname.startsWith('/login');
}

export function installSessionExpiredHandler(axios, win = window) {
    let redirecting = false;
    return axios.interceptors.response.use(undefined, (error) => {
        const status = statusOf(error);
        if ((status === 401 || status === 419)
            && isApiUrl(error?.config?.url)
            && !isExcludedPage(win.location.pathname)) {
            if (!redirecting) {
                redirecting = true;
                // ErrorLogger (console.warn を拾う) に残す
                console.warn('ログインが切れました', status, error.config.url);
                win.alert(SESSION_EXPIRED_MESSAGE);
                win.location.href = '/login';
            }
            // 画面を移るので、呼び出し側の then / catch は走らせない
            return new Promise(() => {});
        }
        return Promise.reject(error);
    });
}
