// EposPrinter の接続・やり直し・印刷が旧 ReceiptPrinter.vue と同じ手順であること (偽の ePOS SDK で確かめる)。
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { EposPrinter, MAX_RETRY } from '../../resources/js/printer/EposPrinter.js';

function fakeEpson({ results }) {
    const log = [];
    const devices = [];
    class ePOSDevice {
        constructor() {
            this.DEVICE_TYPE_PRINTER = 'type_printer';
            devices.push(this);
        }
        connect(ip, port, cb, opts) {
            log.push(['connect', ip, port, opts]);
            const r = results.shift() ?? 'ERROR_TIMEOUT';
            queueMicrotask(() => cb(r));
        }
        createDevice(id, type, opts, cb) {
            log.push(['createDevice', id, type, opts]);
            const printer = new Proxy({}, {
                get(target, name) {
                    if (name in target) return target[name];
                    return (...args) => {
                        log.push([name, ...args]);
                        if (name === 'send') queueMicrotask(() => target.onreceive({ success: true }));
                    };
                },
                set(target, name, v) { target[name] = v; if (name === 'timeout') log.push(['timeout', v]); return true; },
            });
            queueMicrotask(() => cb(printer, 'OK'));
        }
        disconnect() { log.push(['disconnect']); }
    }
    return { ns: { ePOSDevice }, log, devices };
}

beforeEach(() => {
    vi.useFakeTimers();
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
    globalThis.alert = vi.fn();
});
afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
});

describe('EposPrinter', () => {
    test('port 8008・eposprint で接続し、印刷後に切断する', async () => {
        const { ns, log } = fakeEpson({ results: ['OK'] });
        const p = new EposPrinter(() => ns);
        await p.connect('192.168.0.214');
        const res = await p.print([['addText', 'あ'], ['addCut', 'feed']]);

        expect(res).toEqual({ success: true });
        expect(log).toEqual([
            ['connect', '192.168.0.214', 8008, { eposprint: true }],
            ['createDevice', 'local_printer', 'type_printer', { crypto: false, buffer: false }],
            ['timeout', 60000],
            ['addText', 'あ'],
            ['addCut', 'feed'],
            ['send'],
            ['disconnect'],
        ]);
        expect(p.currentDevice).toBeNull();
    });

    test('つながらなければ 1・2・3 秒後にやり直し、4 回目も失敗したら alert して reject', async () => {
        const { ns, log } = fakeEpson({ results: ['ERROR_TIMEOUT', 'ERROR_TIMEOUT', 'ERROR_TIMEOUT', 'ERROR_TIMEOUT'] });
        const p = new EposPrinter(() => ns);
        const done = p.connect('10.0.0.1').catch((e) => e);

        await vi.advanceTimersByTimeAsync(0);
        expect(log.length).toBe(1);
        await vi.advanceTimersByTimeAsync(999);
        expect(log.length).toBe(1);
        await vi.advanceTimersByTimeAsync(1);   // 1 秒後
        expect(log.length).toBe(2);
        await vi.advanceTimersByTimeAsync(2000); // さらに 2 秒後
        expect(log.length).toBe(3);
        await vi.advanceTimersByTimeAsync(3000); // さらに 3 秒後
        expect(log.length).toBe(1 + MAX_RETRY);

        expect(await done).toBeInstanceOf(Error);
        expect(globalThis.alert).toHaveBeenCalledWith('プリンターに接続できませんでした。\nプリンターの電源とネットワーク接続を確認してください。');
    });

    test('やり直しの途中でつながれば印刷できる', async () => {
        const { ns } = fakeEpson({ results: ['ERROR_TIMEOUT', 'OK'] });
        const p = new EposPrinter(() => ns);
        const done = p.connect('10.0.0.1');
        await vi.advanceTimersByTimeAsync(1000);
        await done;
        expect(p.currentDevice).not.toBeNull();
        expect(globalThis.alert).not.toHaveBeenCalled();
    });

    test('前回の接続が残っていたら、次の接続の前に切断する', async () => {
        const { ns, log } = fakeEpson({ results: ['OK', 'OK'] });
        const p = new EposPrinter(() => ns);
        await p.connect('10.0.0.1');
        await p.connect('10.0.0.1');
        expect(log.map((l) => l[0])).toEqual(['connect', 'disconnect', 'connect']);
    });

    test('つながっていないのに print すると reject', async () => {
        const { ns } = fakeEpson({ results: [] });
        await expect(new EposPrinter(() => ns).print([])).rejects.toThrow();
    });

    test('openDrawer はドロワー 1 にパルス 100 を送る', async () => {
        const { ns, log } = fakeEpson({ results: ['OK'] });
        const p = new EposPrinter(() => ns);
        await p.connect('10.0.0.1');
        await p.openDrawer();
        expect(log).toContainEqual(['addPulse', 'drawer_1', 'pulse_100']);
    });
});
