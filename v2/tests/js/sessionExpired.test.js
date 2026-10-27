// @vitest-environment jsdom
// ログイン切れ: API の 401 / 419 で 1 回だけ案内して /login へ。顧客表示の端末・API 以外では何もしない。
import { expect, test, vi } from 'vitest';
import axios from 'axios';
import { installSessionExpiredHandler, SESSION_EXPIRED_MESSAGE } from '../../resources/js/utils/sessionExpired.js';

function setup(pathname, status) {
    const instance = axios.create();
    instance.defaults.adapter = (config) => Promise.reject(Object.assign(new Error('x'), { config, response: { status } }));
    const win = { location: { pathname, href: 'http://h' + pathname }, alert: vi.fn() };
    installSessionExpiredHandler(instance, win);
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    return { instance, win };
}

const settled = (p) => Promise.race([p.then(() => 'resolved', () => 'rejected'), new Promise((r) => setTimeout(() => r('pending'), 20))]);

test.each([401, 419])('POS の画面で API が %i → alert 1 回で /login へ、呼び出し側の catch は走らない', async (status) => {
    const { instance, win } = setup('/order/create', status);
    const a = instance.post('/api/order', {});
    const b = instance.get('/api/order/1');
    expect(await settled(a)).toBe('pending');
    expect(await settled(b)).toBe('pending');
    expect(win.alert).toHaveBeenCalledTimes(1);
    expect(win.alert).toHaveBeenCalledWith(SESSION_EXPIRED_MESSAGE);
    expect(win.location.href).toBe('/login');
});

test.each(['/customer_display', '/display/login/abc', '/login'])('%s の画面では何もしない (そのまま reject)', async (path) => {
    const { instance, win } = setup(path, 401);
    expect(await settled(instance.post('/api/customer-display', {}))).toBe('rejected');
    expect(win.alert).not.toHaveBeenCalled();
});

test('API 以外 (/display/ping, /broadcasting/auth) や 401/419 以外はそのまま reject', async () => {
    const { instance, win } = setup('/order/create', 401);
    expect(await settled(instance.get('/display/ping'))).toBe('rejected');
    expect(await settled(instance.post('/broadcasting/auth', {}))).toBe('rejected');
    const other = setup('/order/create', 500);
    expect(await settled(other.instance.post('/api/order', {}))).toBe('rejected');
    expect(win.alert).not.toHaveBeenCalled();
    expect(other.win.alert).not.toHaveBeenCalled();
});
