import { P } from './printerConstants.js';

/**
 * EPSON ePOS SDK (public/js/epos-2.22.0.js、window.epson) でプリンタにつなぎ、印刷する。
 * 接続のしかたは旧 ReceiptPrinter.vue と同じ:
 *   - 印刷のたびに新しい ePOSDevice で connect(ip, 8008, cb, {eposprint: true})
 *   - 前回の接続が残っていたら先に切断
 *   - 失敗したら 1 秒・2 秒・3 秒後に最大 3 回やり直し、だめなら alert
 *   - createDevice('local_printer', DEVICE_TYPE_PRINTER, {crypto: false, buffer: false})、printer.timeout = 60000
 *   - 印刷結果 (onreceive) を受けたら切断。送信エラー・デバイス作成失敗でも切断
 */
export const MAX_RETRY = 3;
export const PRINT_TIMEOUT = 60000;

export class EposPrinter {
    constructor(epsonNs = () => window.epson) {
        this.epson = epsonNs;
        // 接続中のデバイス (disconnect 用)
        this.currentDevice = null;
    }

    /** 接続する。最大 3 回やり直してもつながらなければ alert を出して reject。 */
    connect(ip, port = 8008) {
        this.disconnect('【接続クリーンアップ】前回の接続を切断します');

        const ePosDev = new (this.epson().ePOSDevice)();
        let retryCount = 0;

        return new Promise((resolve, reject) => {
            const tryConnect = () => {
                console.log('プリンターに接続中... (IP: ' + ip + ', Port: ' + port + ')' + (retryCount > 0 ? ` [リトライ ${retryCount}/${MAX_RETRY}]` : ''));
                ePosDev.connect(ip, port, cbConnect, { eposprint: true });
            };
            const cbConnect = (data) => {
                console.log('【接続結果】', data);
                if (data == 'OK' || data == 'SSL_CONNECT_OK') {
                    this.currentDevice = ePosDev;
                    resolve();
                } else if (retryCount < MAX_RETRY) {
                    retryCount++;
                    console.warn(`【接続失敗】${retryCount}秒後にリトライします...`);
                    setTimeout(tryConnect, 1000 * retryCount);
                } else {
                    console.error('【エラー】プリンターへの接続に失敗しました（リトライ上限）:', data);
                    alert('プリンターに接続できませんでした。\nプリンターの電源とネットワーク接続を確認してください。');
                    reject(new Error('printer connect failed: ' + data));
                }
            };
            tryConnect();
        });
    }

    /**
     * 命令列 (receiptCommands.js の戻り値) をプリンタに送る。印刷結果を受けたら切断して resolve。
     * @param {Array<[string, ...any]>} commands
     */
    print(commands) {
        const ePosDev = this.currentDevice;
        if (!ePosDev) {
            return Promise.reject(new Error('printer is not connected'));
        }

        return new Promise((resolve, reject) => {
            console.log('プリンターデバイス作成中...');
            ePosDev.createDevice('local_printer', ePosDev.DEVICE_TYPE_PRINTER, { crypto: false, buffer: false }, (printer, retcode) => {
                console.log('【デバイス作成結果】', retcode);
                if (retcode != 'OK') {
                    console.error('【エラー】プリンターデバイスの作成に失敗しました:', retcode);
                    this.disconnect();
                    reject(new Error('createDevice failed: ' + retcode));
                    return;
                }
                printer.timeout = PRINT_TIMEOUT;
                printer.onreceive = (res) => {
                    console.log('【印刷完了】印刷結果:', res.success ? '成功' : '失敗');
                    if (!res.success) {
                        console.error('印刷エラー:', res);
                    }
                    this.disconnect();
                    resolve(res);
                };
                printer.oncoveropen = () => {
                    console.warn('【警告】プリンターのカバーが開いています');
                };
                try {
                    for (const [method, ...args] of commands) {
                        printer[method](...args);
                    }
                    printer.send();
                    console.log('【印刷コマンド送信完了】プリンターからの応答を待機中...');
                } catch (error) {
                    console.error('【エラー】印刷コマンド送信中にエラーが発生しました:', error);
                    this.disconnect();
                    reject(error);
                }
            });
        });
    }

    /** ドロワーだけを開ける (レシート印刷では命令列の先頭に addPulse が入るので使わない)。 */
    openDrawer() {
        return this.print([['addPulse', P.DRAWER_1, P.PULSE_100]]);
    }

    disconnect(message = '【接続終了】プリンターから切断します') {
        if (this.currentDevice) {
            console.log(message);
            try {
                this.currentDevice.disconnect();
            } catch (e) {
                console.warn('切断でエラー（無視）:', e);
            }
            this.currentDevice = null;
        }
    }
}

/**
 * 画面を開いたときの接続 (旧 OrderComponent の mounted と同じ。結果はログに出すだけ)。
 * 旧は IP を 192.168.0.214 と書いていた。v2 は設定 (printer_ip) の値。
 */
export function warmUpPrinter(ip, port = 8008) {
    const ePosDev = new window.epson.ePOSDevice();
    ePosDev.connect(ip, port, function (data) {
        if (data == 'OK' || data == 'SSL_CONNECT_OK') {
            console.log('printer connected');
        } else {
            console.log(data);
        }
    }, { eposprint: true });
    return ePosDev;
}
