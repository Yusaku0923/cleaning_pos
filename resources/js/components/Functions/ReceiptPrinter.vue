<template></template>

<script>
export default ({
    props: {
        token: {
            type: String,
            required: true
        },
    },
    methods: {
        fetchReceipt: async function(order_id) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.get('/api/receipt/' + order_id)
            .then(function (response) {
                return response.data;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },

        printReceipt: async function (order_id) {
            let receipt = await this.fetchReceipt(order_id);

            let store_name         = receipt['store_name'];
            let store_address      = receipt['store_address'];
            let store_tel          = receipt['store_tel'];
            let customer_name_kana = receipt['customer_name_kana'];
            let customer_name      = receipt['customer_name'];
            let customer_tel       = receipt['customer_tel'] ?? '';
            let manager_name       = receipt['manager_name'];
            let ordered_at         = receipt['ordered_at'];
            let order_list         = receipt['order_list'];
            let total_count        = receipt['total_count'];
            let amount             = receipt['amount'];
            let discount           = receipt['discount'];
            let payment            = receipt['payment'];
            let tax                = receipt['tax'];
            let paid_at            = receipt['paid_at'];

            let printer = null;
            let ePosDev = new epson.ePOSDevice();
            ePosDev.connect('192.168.0.214', 8008, cbConnect, {"eposprint" : true});

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

                // store name
                printer.addText(store_name);

                printer.addTextSmooth(false);
                printer.addFeed();
                printer.addFeed();
                printer.addTextSize(1, 1);

                // store address, store tel
                printer.addText(store_address + ' (TEL' + store_tel + ')');

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

                // customer name kana
                printer.addText(customer_name_kana);

                printer.addFeed();
                printer.addTextPosition(40);
                printer.addTextSize(2, 2);

                // customer name
                printer.addText(customer_name + ' 様');

                printer.addFeed();
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addTextPosition(20);

                // customer tel
                printer.addText('お電話 ' + customer_tel);

                printer.addTextPosition(250);

                // manager_name
                printer.addText('担当者:' + manager_name);

                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_MEDIUM);
                printer.addFeed();

                // ordered at
                printer.addText(ordered_at);
                printer.addTextSize(2, 2);
                printer.addTextPosition(270);

                // identity
                printer.addText(order_id);

                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();

                // order list
                for (const order of order_list) {
                    if (order['count'] <= 1) {
                        // one line
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag start
                        printer.addText(order['tag_start']);

                        printer.addTextSize(1, 2);
                        printer.addTextPosition(130);

                        // clothes name
                        printer.addText(order['name']);

                        printer.addTextPosition(350);

                        // clothes price
                        printer.addText(order['price'].toLocaleString());

                        printer.addFeed();
                    } else {
                        // multiple line
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag start
                        printer.addText(order['tag_start']);
                        
                        printer.addTextSize(1, 2);
                        printer.addTextPosition(130);
                        
                        // clothes name
                        printer.addText(order['name']);
                        printer.addTextPosition(340);

                        // clothes count
                        printer.addText('X ' + order['count']);

                        printer.addFeed();
                        printer.addTextSize(2, 2);

                        // tag end
                        printer.addText('～' + order['tag_end']);

                        printer.addTextPosition(350);
                        printer.addTextSize(1, 2);

                        // clothes price
                        printer.addText(order['price'].toLocaleString());

                        printer.addFeed();
                    }
                }

                printer.addFeed();
                printer.addHLine(10, 200, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addTextSize(1, 1);
                printer.addText('点数　');
                printer.addTextPosition(100);

                // total count
                printer.addText(total_count.toLocaleString());

                printer.addTextPosition(200);
                printer.addText('小計');
                printer.addTextPosition(340);

                // subtotal
                printer.addText((amount + discount).toLocaleString());
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addText('点数');
                printer.addTextPosition(100);

                // total count
                printer.addText(total_count);

                printer.addTextPosition(200);
                printer.addText('伝票合計');
                printer.addTextPosition(340);

                // total
                printer.addText(amount.toLocaleString());
                printer.addFeed();
                printer.addFeed();
                printer.addTextPosition(200);
                printer.addText('(うち消費税');
                printer.addTextPosition(360);

                // tax
                printer.addText(tax + '）');

                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_MEDIUM);
                printer.addFeed();
                printer.addTextSize(2, 2);
                printer.addTextPosition(50);
                if (paid_at !== null) {
                    printer.addText('合計額');
                    printer.addTextPosition(240);

                    // total
                    printer.addText(amount.toLocaleString());
                    printer.addFeed();
                    printer.addHLine(0, 575, printer.LINE_THIN);
                    printer.addFeed();
                    printer.addTextSize(1, 1);
                    printer.addTextAlign(printer.ALIGN_LEFT);
                    printer.addTextPosition(10);
                    printer.addText('お預り');
                    printer.addTextPosition(120);
                    printer.addText(payment.toLocaleString());
                    printer.addTextPosition(240);
                    printer.addText('お釣り');
                    printer.addTextAlign(printer.ALIGN_RIGHT);
                    printer.addTextPosition(340);
                    printer.addText((payment - amount).toLocaleString());
                } else {
                    printer.addText('未収額');
                    printer.addTextPosition(240);
                    printer.addText((amount - payment).toLocaleString());
                    printer.addFeed();
                    printer.addHLine(0, 575, printer.LINE_THIN);
                }
                printer.addFeed();
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addTextPosition(0);
                printer.addText('いつもご利用ありがとうございます。');
                printer.addFeed();
                printer.addTextPosition(0);
                printer.addText('今後ともよろしくお願いいたします。');
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                if (paid_at === null) {
                    printer.addTextAlign(printer.ALIGN_CENTER);
                    printer.addTextSize(2, 2);
                    printer.addText('未　収');
                }
                printer.addFeed();
                printer.addFeed();
                printer.addCut(printer.CUT_FEED);
                printer.send();
            }
        },
    },
})
</script>
