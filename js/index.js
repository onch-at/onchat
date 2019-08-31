$(function () {
    var historyItem = $(".history-item");
    var msgInput = $("#message");
    var sendBtn = $(".send-btn");

    var count = 0; //旧消息记录条数
    var history = 0; //历史记录条数

    var username;

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

    msgInput.on("input", function () {
        if ($.trim($(this).val()) == "") { //如果压缩空格后内容仍然为空
            sendBtn.attr("disabled", "disabled");
        } else {
            sendBtn.removeAttr("disabled");
        }
    });

    $(".history-btn").click(function () {
        $.ajax({
            type: "GET",
            url: "./php/history.php",
            dataType: "JSON",
            data: {
                history: history
            },
            beforeSend: function (XHR) {
                $(".history-btn > i").addClass("ease-reverse-spin");
                $(".history-btn").attr("disabled", "disabled");
            },
            complete: function (XHR, TS) {
                setTimeout(() => {
                    $(".history-btn > i").removeClass("ease-reverse-spin");
                    $(".history-btn").removeAttr("disabled"); 

                    if (history == 0) {
                        $(".history-btn").attr("disabled", "disabled");
                        $(".history-btn > i").addClass("fa-check");
                        $(".history-btn > i").removeClass("fa-history");

                        setTimeout(() => {
                            historyItem.fadeOut("1500");
                        }, 1000);
                    }
                }, 1000);
            },
            success: function (data) {
                $.each(data, function (k, v) {
                    if (k == "count") { //如果不是对象，则该值为历史记录的条数
                        history = v;
                    } else {
                        setTimeout(() => {
                            if (v.name == username) {
                                historyItem.after('\
                                    <li class="msg-item right-item">\
                                        <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                        <div class="info">\
                                            <div class="username">'+v.name+'</div>\
                                            <div class="msg-bubble">'+v.msg+'</div>\
                                        </div>\
                                    </li>\
                                ');
                            } else {
                                historyItem.after('\
                                    <li class="msg-item left-item">\
                                    <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                        <div class="info">\
                                            <div class="username">'+v.name+'</div>\
                                            <div class="msg-bubble">'+v.msg+'</div>\
                                        </div>\
                                    </li>\
                                ');
                            }
                            
                        }, k * 50);
                    }
                });
            },
            error: function (XHR) { },
            timeout: 5000
        });
    });

    sendBtn.click(function () {
        $.ajax({
            type: "POST",
            url: "./php/do.php",
            dataType: "JSON",
            data: {
                msg: $("#message").val(),
            },
            beforeSend: function (XHR) {
                sendBtn.attr("disabled", "disabled");
            },
            complete: function (XHR, TS) {
                sendBtn.removeAttr("disabled");
            },
            success: function (data) {
                if (data) {
                    msgInput.val(""); //清空消息输入框
                    setTimeout(() => { //并禁用发送按钮（必须延迟执行，否则无法禁用）
                        sendBtn.attr("disabled", "disabled");
                    }, 0);
                } else {
                    showModal("请先登录再进行操作！");

                    setTimeout(() => {
                        location.href='./user/login'; //跳到登录页面
                    }, 2000);
                }
            },
            error: function (XHR) { }
        });
    });

    $.ajax({
        type: "GET",
        url: "./php/username.php",
        dataType: "JSON",
        beforeSend: function (XHR) { },
        complete: function (XHR, TS) {
            
        },
        success: function (data) {
            username = data;
            last();
        },
        error: function (XHR) { }
    });

    //预先加载出最后5条消息
    function last() {
        $.ajax({
            type: "GET",
            url: "./php/last.php",
            dataType: "JSON",
            beforeSend: function (XHR) { },
            complete: function (XHR, TS) {
                loop();
            },
            success: function (data) {
                $.each(data, function (k, v) {
                    if (k == "count") { //如果不是对象，则该值为旧消息记录的条数
                        count = v;
                        if (v > 5) { //r如果旧消息记录总条数大于5条，即还有消息没有被预加载出来，则显示历史按钮
                            history = v - 5; //记录历史记录条数
                            historyItem.removeClass("d-none");
                        }
                    } else {
                        if (v.name == username) {
                            $(".msg-list").append('\
                                <li class="msg-item right-item">\
                                    <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                    <div class="info">\
                                        <div class="username">'+v.name+'</div>\
                                        <div class="msg-bubble">'+v.msg+'</div>\
                                    </div>\
                                </li>\
                            ');
                        } else {
                            $(".msg-list").append('\
                                <li class="msg-item left-item">\
                                <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                    <div class="info">\
                                        <div class="username">'+v.name+'</div>\
                                        <div class="msg-bubble">'+v.msg+'</div>\
                                    </div>\
                                </li>\
                            ');
                        }
                    }
                });
    
                $(".msg-item").hide().each(
                    function (index) { //加上index就能实现一个显示出来再显示下一个
                        $(this).delay(50 * index).fadeIn(500);
                    }
                );
    
                setTimeout(() => {
                    backToBottom(500);
                }, 150);
            },
            error: function (XHR) { }
        });
    }

    function loop() {
        setTimeout(() => {
            $.ajax({
                type: "GET",
                url: "./php/get.php",
                dataType: "JSON",
                data: {
                  count: count, //旧消息记录条数
                },
                beforeSend: function (XHR) { },
                complete: function (XHR, TS) {
                    loop();
                },
                success: function (data) {
                    $.each(data, function (k, v) {
                        if (k == "count") { //如果不是对象，则该值为旧消息记录的条数
                            count = v;
                        } else {
                            if (v.name == username) {
                                $(".msg-list").append('\
                                    <li class="msg-item right-item">\
                                        <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                        <div class="info">\
                                            <div class="username">'+v.name+'</div>\
                                            <div class="msg-bubble">'+v.msg+'</div>\
                                        </div>\
                                    </li>\
                                ');
                            } else {
                                $(".msg-list").append('\
                                    <li class="msg-item left-item">\
                                    <img class="user-portrait rounded-circle" src="https://q.qlogo.cn/headimg_dl?dst_uin=1838491745&spec=5" alt="" srcset="">\
                                        <div class="info">\
                                            <div class="username">'+v.name+'</div>\
                                            <div class="msg-bubble">'+v.msg+'</div>\
                                        </div>\
                                    </li>\
                                ');
                            }

                            backToBottom(500);
                        }
                    });
                },
                error: function (XHR) { },
                timeout: 500
            });
        }, 500);
    }
});