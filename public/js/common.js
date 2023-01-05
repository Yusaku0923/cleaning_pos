showClock();

function showClock() {
    var nowTime = new Date();
    var nowYear = nowTime.getFullYear();
    var nowMonth = ("0"+(nowTime.getMonth() + 1)).slice(-2);
    var nowDate = ("0"+nowTime.getDate()).slice(-2)
    var nowHour = nowTime.getHours();
    var nowMin  = nowTime.getMinutes();
    var msg = nowYear + '年' + nowMonth + '月' + nowDate + '日 ' + nowHour + '時' + nowMin + '分';
    document.getElementById("RealtimeClockArea").innerHTML = msg;
}
setInterval('showClock()', 1000);
