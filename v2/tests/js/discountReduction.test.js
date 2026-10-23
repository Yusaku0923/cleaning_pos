// 割引 (%) の値引額: 画面 (utils/money.js discountReduction) が サーバー (PriceCalculator::reduction) と全件一致すること。
// tests/fixtures/discount_reduction.json は PHP で 単価 10〜30000 (10 刻み) × 割合 1〜100 の 300,000 通りを
// "単価:割合:値引額\n" で並べた sha256 (作り直しの手順は task-6-report.md の Fix round 1)。
// 旧は浮動小数 Math.floor(amount * (discount / 100)) で、まれに 1 円ずれる (e2e/allowed-diffs.md)。
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { expect, test } from 'vitest';
import { discountReduction } from '../../resources/js/utils/money.js';

const fx = JSON.parse(fs.readFileSync(path.resolve(__dirname, '../fixtures/discount_reduction.json'), 'utf8'));

function digest(fn) {
    const h = crypto.createHash('sha256');
    let n = 0;
    for (let a = 10; a <= 30000; a += 10) {
        for (let r = 1; r <= 100; r++) {
            h.update(`${a}:${r}:${fn(a, r)}\n`);
            n++;
        }
    }
    return [n, h.digest('hex')];
}

test('PHP の PriceCalculator::reduction と 300,000 通りすべて一致', () => {
    expect(digest(discountReduction)).toEqual([fx.count, fx.sha256]);
    for (const [a, r, red] of fx.samples) expect(discountReduction(a, r)).toBe(red);
});

test('旧の浮動小数の計算はずれる組み合わせがある (このテストがずれを見分けられること)', () => {
    const legacy = (a, r) => Math.floor(a * (r / 100));
    expect(digest(legacy)[1]).not.toBe(fx.sha256);
    expect(legacy(100, 29)).toBe(28);
    expect(discountReduction(100, 29)).toBe(29);
    expect(discountReduction(1500, 29)).toBe(435);
});

test('割合 0 は値引なし', () => {
    expect(discountReduction(1234, 0)).toBe(0);
});
