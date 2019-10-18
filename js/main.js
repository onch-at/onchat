$(function () {
    var rightBtn = $(".right-btn");
    var msgList = $(".msg-list");
    var historyItem = $(".history-item");
    var historyBtn = $(".history-btn");
    var msgInput = $("#message");
    var sendBtn = $(".send-btn");

    $.ajax({
        type: "GET",
        url: "../php/is-login.php",
        dataType: "JSON",
        beforeSend: function (XHR) { },
        complete: function (XHR, TS) {
            
        },
        success: function (data) {
            if (!data) {
                // msgInput.attr("placeholder", "登录后即可开始聊天！");
                // msgInput.attr("readonly", "readonly");
            }
        },
        error: function (XHR) { }
    });

    msgInput.on("input", function () {
        if ($.trim($(this).val()) == "") { //如果压缩空格后内容仍然为空
            sendBtn.attr("disabled", "disabled");
        } else {
            sendBtn.removeAttr("disabled");
        }
    });
    Cookies.set("PHPSESSID", "lqj2gq9a03acc3u5fcv9j21je7");
    const socket = new WebSocket("ws://test.hypergo.net:9501?sessid=" + Cookies.get("PHPSESSID") + "&rid=1");
    // Connection opened
    socket.addEventListener('open', function (event) {
        console.log("与服务器握手成功！");
    });
    // Listen for messages
    socket.addEventListener('message', function (event) {
        console.log("服务器对客户端说：", event.data);
    });

    sendBtn.click(function () {
        var data = JSON.stringify({
            cmd: "chat",
            data: {
                msg: $("#message").val(),
                style: []
            }
        });
        
        $("#message").val("");
        socket.send(data);
    });

});