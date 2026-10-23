// @vitest-environment jsdom
// 顧客表示: 送信は POST /api/customer-display (旧と同じペイロード)、失敗しても例外を出さない。
// Pusher の鍵が無いときは Echo を作らない (表示画面の JS が止まらない)。
import { afterEach, expect, test, vi } from 'vitest';
import { pushCustomerDisplay, pushFlashedCustomerDisplay } from '../../resources/js/customerDisplay.js';
import { setupEcho } from '../../resources/js/bootstrap.js';

afterEach(() => {
    document.head.innerHTML = '';
    delete window.Echo;
    vi.restoreAllMocks();
});

test('POST /api/customer-display に旧と同じペイロードを送る', async () => {
    const post = vi.spyOn(window.axios, 'post').mockResolvedValue({ data: [] });
    await pushCustomerDisplay({ event: 'finish' });
    expect(post).toHaveBeenCalledWith('/api/customer-display', { event: 'finish' });
});

test('送信に失敗しても reject しない (POS を止めない)', async () => {
    vi.spyOn(window.axios, 'post').mockRejectedValue(new Error('500'));
    vi.spyOn(console, 'log').mockImplementation(() => {});
    await expect(pushCustomerDisplay({ event: 'finish' })).resolves.toBeUndefined();
});

test('フラッシュの meta があれば 1 回送る、無ければ送らない', async () => {
    const post = vi.spyOn(window.axios, 'post').mockResolvedValue({ data: [] });
    expect(pushFlashedCustomerDisplay()).toBeNull();
    expect(post).not.toHaveBeenCalled();

    const meta = document.createElement('meta');
    meta.name = 'customer-display-push';
    meta.content = JSON.stringify({ event: 'customer', name: '山田' });
    document.head.appendChild(meta);
    await pushFlashedCustomerDisplay();
    expect(post).toHaveBeenCalledWith('/api/customer-display', { event: 'customer', name: '山田' });
});

test('Pusher の鍵が無ければ Echo を作らず null (例外を出さない)', async () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    await expect(setupEcho({})).resolves.toBeNull();
    await expect(setupEcho({ VITE_PUSHER_APP_KEY: '' })).resolves.toBeNull();
    expect(window.Echo).toBeUndefined();
});

test('鍵があれば private channel を使える Echo を作る', async () => {
    const echo = await setupEcho({ VITE_PUSHER_APP_KEY: 'k', VITE_PUSHER_APP_CLUSTER: 'mt1' });
    expect(echo).toBe(window.Echo);
    expect(typeof echo.private).toBe('function');
    echo.disconnect();
});
