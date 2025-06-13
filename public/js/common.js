// DOMが読み込まれてから実行
document.addEventListener('DOMContentLoaded', function() {
    showClock();
    setInterval(showClock, 1000);
});

function showClock() {
    var clockElement = document.getElementById("RealtimeClockArea");
    
    // 要素が存在しない場合は処理を終了
    if (!clockElement) {
        return;
    }
    
    var nowTime = new Date();
    var nowYear = nowTime.getFullYear();
    var nowMonth = ("0"+(nowTime.getMonth() + 1)).slice(-2);
    var nowDate = ("0"+nowTime.getDate()).slice(-2)
    var nowHour = nowTime.getHours();
    var nowMin  = nowTime.getMinutes();
    var msg = nowYear + '年' + nowMonth + '月' + nowDate + '日 ' + nowHour + '時' + nowMin + '分';
    clockElement.innerHTML = msg;
}
