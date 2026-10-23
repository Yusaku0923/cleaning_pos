// ePOS SDK (public/js/epos-2.22.0.js) のプリンタ定数のうち、レシートで使うもの。
// 値は SDK と同じ (receiptCommands.test.js が本物の SDK の Printer から読んだ値と突き合わせる)。
export const P = Object.freeze({
    FONT_B: 'font_b',
    ALIGN_CENTER: 'center',
    ALIGN_LEFT: 'left',
    ALIGN_RIGHT: 'right',
    COLOR_1: 'color_1',
    LINE_MEDIUM: 'medium',
    LINE_THIN: 'thin',
    DRAWER_1: 'drawer_1',
    PULSE_100: 'pulse_100',
    CUT_FEED: 'feed',
});
