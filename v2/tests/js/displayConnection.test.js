// @vitest-environment jsdom
// 顧客表示のキープアライブとチャンネル認可。セッション切れ (419) は ping で作り直して 1 回だけやり直す。
// ログインが切れた (401/403) ときは黙って止まらず、スタッフ向けの案内に切り替える。
import { expect, test, vi } from 'vitest';
import { createDisplayConnection, KEEPALIVE_MS } from '../../resources/js/displayConnection.js';

const httpError = (status) => Object.assign(new Error('HTTP ' + status), { response: { status } });
const quiet = () => vi.spyOn(console, 'warn').mockImplementation(() => {});

test('キープアライブは 10 分ごとに GET /display/ping', () => {
    const axios = { get: vi.fn().mockResolvedValue({ status: 204 }) };
    const timer = vi.fn();
    const conn = createDisplayConnection({ axios, onLoginRequired: vi.fn() });
    conn.startKeepAlive(timer);
    expect(KEEPALIVE_MS).toBe(10 * 60 * 1000);
    expect(timer).toHaveBeenCalledWith(expect.any(Function), KEEPALIVE_MS);
    timer.mock.calls[0][0]();
    expect(axios.get).toHaveBeenCalledWith('/display/ping');
});

test('キープアライブが 401 ならスタッフ向けの案内 (1 回だけ)', async () => {
    quiet();
    const onLoginRequired = vi.fn();
    const axios = { get: vi.fn().mockRejectedValue(httpError(401)) };
    const conn = createDisplayConnection({ axios, onLoginRequired });
    expect(await conn.ping()).toBe(false);
    await conn.ping();
    expect(onLoginRequired).toHaveBeenCalledTimes(1);
});

test('通信エラー (オフライン等) では案内に切り替えない', async () => {
    quiet();
    const onLoginRequired = vi.fn();
    const conn = createDisplayConnection({ axios: { get: vi.fn().mockRejectedValue(new Error('Network Error')) }, onLoginRequired });
    expect(await conn.ping()).toBe(false);
    expect(onLoginRequired).not.toHaveBeenCalled();
});

test('認可は POST /broadcasting/auth の結果を Pusher に返す', async () => {
    const axios = { post: vi.fn().mockResolvedValue({ data: { auth: 'k:sig' } }), get: vi.fn() };
    const conn = createDisplayConnection({ axios, onLoginRequired: vi.fn() });
    const cb = vi.fn();
    await conn.authorize({ socketId: '1.2', channelName: 'private-store.1.display' }, cb);
    expect(axios.post).toHaveBeenCalledWith('/broadcasting/auth', { socket_id: '1.2', channel_name: 'private-store.1.display' });
    expect(cb).toHaveBeenCalledWith(null, { auth: 'k:sig' });
});

test('セッション切れ (419) は ping で入り直して 1 回だけやり直す', async () => {
    const axios = {
        post: vi.fn().mockRejectedValueOnce(httpError(419)).mockResolvedValueOnce({ data: { auth: 'k:sig2' } }),
        get: vi.fn().mockResolvedValue({ status: 204 }),
    };
    const onLoginRequired = vi.fn();
    const conn = createDisplayConnection({ axios, onLoginRequired });
    const cb = vi.fn();
    await conn.authorize({ socketId: '1.2', channelName: 'c' }, cb);
    expect(axios.get).toHaveBeenCalledWith('/display/ping');
    expect(axios.post).toHaveBeenCalledTimes(2);
    expect(cb).toHaveBeenCalledWith(null, { auth: 'k:sig2' });
    expect(onLoginRequired).not.toHaveBeenCalled();
});

test('認可が 401/403 ならエラーを返し、スタッフ向けの案内に切り替える', async () => {
    quiet();
    for (const status of [401, 403]) {
        const onLoginRequired = vi.fn();
        const conn = createDisplayConnection({ axios: { post: vi.fn().mockRejectedValue(httpError(status)), get: vi.fn() }, onLoginRequired });
        const cb = vi.fn();
        await conn.authorize({ socketId: '1.2', channelName: 'c' }, cb);
        expect(cb.mock.calls[0][0]).toBeInstanceOf(Error);
        expect(cb.mock.calls[0][1]).toBeNull();
        expect(onLoginRequired).toHaveBeenCalledTimes(1);
    }
});

test('419 のあと ping も 401 なら案内に切り替える', async () => {
    quiet();
    const onLoginRequired = vi.fn();
    const conn = createDisplayConnection({
        axios: { post: vi.fn().mockRejectedValue(httpError(419)), get: vi.fn().mockRejectedValue(httpError(401)) },
        onLoginRequired,
    });
    const cb = vi.fn();
    await conn.authorize({ socketId: '1.2', channelName: 'c' }, cb);
    expect(cb.mock.calls[0][0]).toBeInstanceOf(Error);
    expect(onLoginRequired).toHaveBeenCalledTimes(1);
});
