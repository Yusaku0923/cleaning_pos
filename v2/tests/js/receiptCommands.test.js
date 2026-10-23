// @vitest-environment jsdom
// 新 buildReceiptCommands と、旧 ReceiptPrinter.vue から写した印刷命令の組み立て (legacyReceiptCommands.js) に
// 同じレシートデータを与え、プリンタへの呼び出し列が完全に一致することを確かめる。
// 旧側の printer は、本物の ePOS SDK (public/js/epos-2.22.0.js) の Printer の定数を持ち、呼び出しを記録するだけの代役。
import fs from 'node:fs';
import path from 'node:path';
import { afterAll, beforeAll, describe, expect, test, vi } from 'vitest';
import { buildReceiptCommands } from '../../resources/js/printer/receiptCommands.js';
import { P } from '../../resources/js/printer/printerConstants.js';
import { legacyReceiptCommands } from './legacyReceiptCommands.js';

const root = path.resolve(__dirname, '../..');
const fixtureDir = path.join(root, 'tests/fixtures/receipts');
const fixtures = fs.readdirSync(fixtureDir).filter((f) => f.endsWith('.json')).sort();

/** SDK を読み込み、createDevice が返すのと同じ Printer クラスのインスタンスを作る */
function realSdkPrinter() {
    const src = fs.readFileSync(path.join(root, 'public/js/epos-2.22.0.js'), 'utf8');
    const hook = 'window.epson.ePOSDevice=ePOSDevice;';
    expect(src.includes(hook)).toBe(true);
    // SDK 本体は変えずに、内部の Printer クラスをテストから見えるようにするだけ
    new Function('window', src.replace(hook, hook + 'window.epson.__Printer=Printer;'))(window);
    return new window.epson.__Printer('local_printer', false, {});
}

/** 定数は本物の SDK の値を返し、メソッド呼び出しは [名前, ...引数] として記録する printer */
function recordingPrinter(sdkPrinter) {
    const calls = [];
    const printer = new Proxy({}, {
        get(_, name) {
            if (typeof name === 'string' && /^[A-Z0-9_]+$/.test(name)) {
                const v = sdkPrinter[name];
                if (v === undefined) throw new Error(`SDK に定数がない: ${name}`);
                return v;
            }
            return (...args) => { calls.push([name, ...args]); };
        },
    });
    return { printer, calls };
}

let sdkPrinter;
beforeAll(() => {
    sdkPrinter = realSdkPrinter();
    // 旧コードの console.log を黙らせる
    vi.spyOn(console, 'log').mockImplementation(() => {});
});
afterAll(() => vi.restoreAllMocks());

describe('printerConstants', () => {
    test('SDK の Printer と同じ値', () => {
        for (const [k, v] of Object.entries(P)) expect([k, sdkPrinter[k]]).toEqual([k, v]);
    });
});

describe('buildReceiptCommands は旧 ReceiptPrinter.vue と同じ命令列', () => {
    test('フィクスチャがそろっている', () => {
        expect(fixtures).toEqual(expect.arrayContaining(['normal.json', 'discount.json', 'invoice.json', 'tags-20.json', 'wrap-10999.json']));
    });

    test.each(fixtures)('%s', (file) => {
        const { order_id, is_reissue, receipt } = JSON.parse(fs.readFileSync(path.join(fixtureDir, file), 'utf8'));

        const { printer, calls } = recordingPrinter(sdkPrinter);
        legacyReceiptCommands(structuredClone(receipt), order_id, is_reissue, printer, () => {});

        const cmds = buildReceiptCommands(structuredClone(receipt), {}, { orderId: order_id, isReissue: is_reissue });

        // 旧は最後に printer.send() を呼ぶ。新は送信を EposPrinter.print に任せるので命令列には入れない
        expect(calls.at(-1)).toEqual(['send']);
        expect(cmds).toEqual(calls.slice(0, -1));
        expect(cmds.length).toBeGreaterThan(80);
    });

    test('ドロワーは初回・現金・支払い済みのときだけ開く', () => {
        const load = (f) => JSON.parse(fs.readFileSync(path.join(fixtureDir, f), 'utf8'));
        const first = (f, isReissue = false) => buildReceiptCommands(load(f).receipt, {}, { orderId: 1, isReissue })[0];
        expect(first('normal.json')).toEqual(['addPulse', 'drawer_1', 'pulse_100']);
        expect(first('normal.json', true)).toEqual(['addFeed']);
        expect(first('invoice.json')).toEqual(['addFeed']);
        expect(first('unpaid.json')).toEqual(['addFeed']);
    });

    test('settings で店名・住所・電話を上書きできる (省略時はレシートの値)', () => {
        const { receipt } = JSON.parse(fs.readFileSync(path.join(fixtureDir, 'normal.json'), 'utf8'));
        const cmds = buildReceiptCommands(receipt, { storeName: '店', address: '住所', tel: '1' }, { orderId: 1 });
        expect(cmds).toContainEqual(['addText', '店']);
        expect(cmds).toContainEqual(['addText', '住所 (TEL1)']);
    });
});
