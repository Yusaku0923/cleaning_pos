<template></template>

<script>
export default ({
    methods: {
        printReceipt: function (order_id) {
            console.log(order_id);

            var store_name = ''
            var store_address = ''
            var store_tel = ''
            var customer_name_kana = ''
            var customer_name = ''
            var customer_tel = ''
            var manager_name = ''
            var total_count = ''
            
            var printer = null;
            var ePosDev = new epson.ePOSDevice();
            ePosDev.connect('192.168.192.168', 8008, cbConnect);
            
            function cbConnect(data) {
                if(data == 'OK' || data == 'SSL_CONNECT_OK') {
                    ePosDev.createDevice('local_printer', ePosDev.DEVICE_TYPE_PRINTER,
                                        {'crypto':false, 'buffer':false}, cbCreateDevice_printer);
                } else {
                    console.log(data);
                }
            }
            function cbCreateDevice_printer(devobj, retcode) {
                if( retcode == 'OK' ) {
                    printer = devobj;
                    printer.timeout = 60000;
                    printer.onreceive = function (res) { console.log(res.success); };
                    printer.oncoveropen = function () { console.log('coveropen'); };
                    print();
                } else {
                    console.log(retcode);
                }
            }
            
            function print() {
                printer.addFeed();
                printer.addFeed();
                printer.addTextFont(printer.FONT_B);
                printer.addTextLineSpace(30);
                printer.addTextLang('ja');
                printer.addTextSmooth(true);
                printer.addTextAlign(printer.ALIGN_CENTER);
                printer.addTextSize(1, 2);
                printer.addText('テストクリーニング');
                printer.addTextSmooth(false);
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addText('東京都新宿区西新宿1-1-1 (TEL 11-2222)');
                printer.addFeed();
                printer.addFeed();
                printer.addTextStyle(true, false, true, printer.COLOR_1);
                printer.addTextSize(2, 2);
                printer.addText('お預り票');
                printer.addFeed();
                printer.addFeed();
                printer.addTextAlign(printer.ALIGN_LEFT);
                printer.addTextPosition(20);
                printer.addTextSize(1, 1);
                printer.addTextStyle(false, false, false, printer.COLOR_1);
                printer.addText('お名前');
                printer.addFeed();
                printer.addTextPosition(40);
                printer.addText('タナカタロウ');
                printer.addFeed();
                printer.addTextPosition(40);
                printer.addTextSize(2, 2);
                printer.addText('田中太郎 様');
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addTextPosition(20);
                printer.addText('お電話 080-5555-6666');
                printer.addTextPosition(250);
                printer.addText('担当者:本店レジ');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_MEDIUM);
                printer.addFeed();
                printer.addText('2022年11月11日 12時12分');
                printer.addTextSize(2, 2);
                printer.addTextPosition(250);
                printer.addText('55555');
                printer.addTextSize(1, 1);
                printer.addText('-01/01');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addTextSize(2, 2);
                printer.addText('0-001');
                printer.addTextSize(1, 2);
                printer.addTextPosition(130);
                printer.addText('絹ブラウス');
                printer.addTextPosition(350);
                printer.addText('1,000');
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addTextSize(2, 2);
                printer.addText('0-002');
                printer.addTextSize(1, 2);
                printer.addTextPosition(130);
                printer.addText('絹ブラウス');
                printer.addTextPosition(340);
                printer.addText('X 4');
                printer.addFeed();
                printer.addTextSize(2, 2);
                printer.addText('～0-005');
                printer.addTextPosition(350);
                printer.addTextSize(1, 2);
                printer.addText('1,000');
                printer.addFeed();
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addTextSize(1, 1);
                printer.addText('点数　');
                printer.addTextPosition(100);
                printer.addText('5');
                printer.addTextPosition(200);
                printer.addText('小計');
                printer.addTextPosition(340);
                printer.addText('5,000');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addText('点数');
                printer.addTextPosition(100);
                printer.addText('5');
                printer.addTextPosition(200);
                printer.addText('伝票合計');
                printer.addTextPosition(340);
                printer.addText('5,000');
                printer.addFeed();
                printer.addFeed();
                printer.addTextPosition(200);
                printer.addText('(うち消費税');
                printer.addTextPosition(360);
                printer.addText('909）');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_MEDIUM);
                printer.addFeed();
                printer.addTextSize(2, 2);
                printer.addTextPosition(50);
                printer.addText('未収額');
                printer.addTextPosition(240);
                printer.addText('5,000');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addTextPosition(20);
                printer.addText('いつもご利用ありがとうございます。');
                printer.addFeed();
                printer.addTextPosition(20);
                printer.addText('今後ともよろしくお願いいたします。');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextAlign(printer.ALIGN_CENTER);
                printer.addTextSize(2, 2);
                printer.addText('未　収');
                printer.addFeed();
                printer.addFeed();
                printer.addCut(printer.CUT_FEED);
                printer.send();
            }
        }
    },
})
</script>
