$(function () {
    var rightBtn = $(".right-btn");
    var msgList = $(".msg-list");
    var historyItem = $(".history-item");
    var historyBtn = $(".history-btn");
    var msgInput = $("#message");
    var sendBtn = $(".send-btn");

    var lenght; //旧消息段条数

    var showModal = function (text) {
        $(".modal-body").text(text);
        $(".modal").modal("show");
    };

    function backToTop(time) {
        $("html").animate({scrollTop: 0}, time);
    }

    function backToBottom(time) {
        $("html").animate({scrollTop: $(".msg-list")[0].scrollHeight}, time);
    }

    function addTime(time) {
        msgList.append('<li class="time-item text-center">'+time+'</li>');
    }

    function addMsgItem(msgObj) {
        if (msgObj.timeout !== false) addTime(msgObj.timeout);

        // if (msgObj.name == username) {
        //     msgList.append('\
        //         <li class="msg-item right-item">\
        //             <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
        //             <div class="info">\
        //                 <div class="username">'+msgObj.name+'</div>\
        //                 <div class="msg-bubble">'+msgObj.msg+'</div>\
        //             </div>\
        //         </li>\
        //     ');
        // } else {
            msgList.append('\
                <li class="msg-item left-item">\
                    <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                    <div class="info">\
                        <div class="username">'+msgObj.uid+'</div>\
                        <div class="msg-bubble">'+msgObj.msg+'</div>\
                    </div>\
                </li>\
            ');
        // }
    }

    $.ajax({
        type: "GET",
        url: "../php/is-login.php",
        dataType: "JSON",
        beforeSend: function (XHR) { },
        complete: function (XHR, TS) { },
        success: function (data) {
            if (!data) {
                //showModal("请先登录！");
                // setTimeout(function () { location.href = "../user/login"; }, 1500);
                // msgInput.attr("placeholder", "登录后即可开始聊天！");
                // msgInput.attr("readonly", "readonly");
            }
        },
        error: function (XHR) { }
    });

    msgInput.click(function () { //点击输入框，回到消息列表最底部
        backToBottom(500);
    });

    msgInput.on("input", function () {
        if ($.trim($(this).val()) === "") { //如果压缩空格后内容仍然为空
            sendBtn.attr("disabled", "disabled");
        } else {
            sendBtn.removeAttr("disabled");
        }
    });

    Cookies.set("PHPSESSID", "eicp48n8lf7bkt900eidvkvdp6");
    const socket = new WebSocket("ws://test.hypergo.net:9501?sessid=" + Cookies.get("PHPSESSID") + "&rid=0");
    // Connection opened
    socket.addEventListener('open', function (event) {
        console.log("与服务器握手成功！");
    });
    // Listen for messages
    socket.addEventListener('message', function (event) {
        console.log("服务器对客户端说：", event.data);

        var msgObj = JSON.parse(event.data);
        
        switch (msgObj.cmd) {
            case "last":
                $(".spinner-item").addClass("d-none"); //隐藏loading

                $.each(msgObj.data, function (k, v) {
                    if (k == "lenght") {
                        if (v > 1) historyItem.removeClass("d-none"); //如果消息段长度大于1，即代表还有记录可以加载
                        lenght = --v;
                    } else {
                        addMsgItem(v);
                        // $("html").stop();
                        // backToBottom(500);
                    }
                });

                
                $(".msg-item").hide().each(function (index) {
                    var time = 50 * index;
                    $(this).delay(time).fadeIn(500);

                    setTimeout(() => {
                        $("html").stop();
                        backToBottom(250);
                    }, time);
                });
                
                break;

            case "chat":
                addMsgItem(msgObj.data);
                backToBottom(500);
                break;

            case "history":
                $.each(msgObj.data.reverse(), function (k, v) {
                    setTimeout(() => {
                        historyItem.after('\
                            <li class="msg-item left-item">\
                                <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                <div class="info">\
                                    <div class="username">'+v.uid+'</div>\
                                    <div class="msg-bubble">'+v.msg+'</div>\
                                </div>\
                            </li>\
                        ');

                        if (v.timeout !== false) historyItem.after('<li class="time-item text-center">'+v.timeout+'</li>');
                    }, k * 50);
                });

                setTimeout(() => {
                    $(".history-btn > i").removeClass("ease-reverse-spin");
                    historyBtn.removeAttr("disabled"); 

                    if (lenght == 0) {
                        historyBtn.attr("disabled", "disabled");
                        $(".history-btn > i").addClass("fa-check");
                        $(".history-btn > i").removeClass("fa-history");

                        historyBtn.tooltip('hide');

                        setTimeout(() => {
                            historyItem.fadeOut("1500");
                        }, 1000);
                    }
                }, 1000);
                break;

            default:
                showModal("未知指令！");
                break;
        }
    });

    historyBtn.click(function () {
        $(".history-btn > i").addClass("ease-reverse-spin");
        historyBtn.attr("disabled", "disabled");
        
        var data = JSON.stringify({
            cmd: "history",
            data: {
                num: lenght--
            }
        });

        socket.send(data);
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