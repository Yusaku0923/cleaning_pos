showClock();

function showClock() {
    var nowTime = new Date();
    var nowYear = nowTime.getFullYear();
    var nowMonth = nowTime.getMonth();
    var nowDate = nowTime.getDate();
    var nowHour = nowTime.getHours();
    var nowMin  = nowTime.getMinutes();
    var msg = nowYear + '年' + nowMonth + '月' + nowDate + '日 ' + nowHour + '時' + nowMin + '分';
    document.getElementById("RealtimeClockArea").innerHTML = msg;
}
setInterval('showClock()', 1000);