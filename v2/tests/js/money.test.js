// 画面の「内消費税」(utils/money.js) が PHP の PriceCalculator::includedTax と同じ値になること。
// tests/fixtures/included_tax.json は PHP で出した値 ([金額, 10%四捨五入, 10%切り捨て, 8%四捨五入, 8%切り捨て])。
// 作り直し: docker compose -f docker-compose.v2.yml exec app php -r '…' (task-6-report.md 参照)
import fs from 'node:fs';
import path from 'node:path';
import { expect, test } from 'vitest';
import { includedTax } from '../../resources/js/utils/money.js';

const rows = JSON.parse(fs.readFileSync(path.resolve(__dirname, '../fixtures/included_tax.json'), 'utf8'));

test('PHP の includedTax と全件一致', () => {
    expect(rows.length).toBeGreaterThan(1000);
    const mismatch = rows.filter(([a, r10, f10, r8, f8]) =>
        includedTax(a, 10, 'round') !== r10 || includedTax(a, 10, 'floor') !== f10
        || includedTax(a, 8, 'round') !== r8 || includedTax(a, 8, 'floor') !== f8);
    expect(mismatch).toEqual([]);
});

test('floor は旧画面の Math.trunc(amount - amount / 1.1) と同じ (正の金額)', () => {
    for (const [a, , f10] of rows.filter(([a]) => a >= 0 && a <= 1215)) {
        expect([a, f10]).toEqual([a, Math.trunc(a - a / 1.1)]);
    }
});

test('例: 税込 500 円は 45 円、505 円は 四捨五入 46 円 / 切り捨て 45 円 (e2e/allowed-diffs.md)', () => {
    expect(includedTax(500, 10, 'round')).toBe(45);
    expect(includedTax(505, 10, 'round')).toBe(46);
    expect(includedTax(505, 10, 'floor')).toBe(45);
    expect(() => includedTax(1, 10, 'ceil')).toThrow();
});
