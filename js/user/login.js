$(function () {
    $.ajax({
        type: "GET",
        url: "../../php/is-login.php",
        dataType: "JSON",
        beforeSend: function (XHR) { },
        complete: function (XHR, TS) { },
        success: function (data) {
            if (data) location.href = "../../";
        },
        error: function (XHR) { }
    });
    
    var login = $(".login-btn");

    function showModal(text) {
        $(".modal-body").html(text);
        $(".modal").modal("show");
    };
    
    //检测表单是否全部已经填写完毕（不论对错）
    function checkForm() {
        var result = true;
        $("form input").each(function () { //each是异步方法
            if ($.trim($(this).val()) == "") result = false;
        });
  
        return result;
    }
  
    function checkInput(input, minLength, maxLength) {
        var length = input.val().length; //输入框内容长度
        if (length < minLength || length > maxLength) { //不符合
            if (!input.hasClass("is-invalid")) input.addClass("is-invalid");
        } else {
            if (input.hasClass("is-invalid")) input.removeClass("is-invalid");
        }
    }
  
    //即时验证
    $("#username").on("input propertychange", function () { checkInput($(this), 5, 30); });
    $("#password").on("input propertychange", function () { checkInput($(this), 8, 50); });
    
    //提交表单处理
    login.click(function () {
        if (!checkForm()) {
            showModal("请先将表单填写完整！");
            return false;
        }
    
        if ($("input").hasClass("is-invalid")) {
            showModal("请先将表单填写正确！");
            return false;
        }
        
        $.ajax({
            type: "POST",
            url: "../action/login.php",
            dataType: "JSON",
            data: {
                username: $("#username").val(),
                password: $("#password").val(),
            },
            beforeSend: function (XHR) {
                login.text("登录中...");
            },
            complete: function (XHR, TS) {
                if (login.text() !== "登录") login.text("登录");
            },
            success: function (data) {
                switch(data.status_code) {
                    case 0: //成功
                        showModal("登录成功，准备跳转...");
            
                        var url = Arg("jump"); //这里get到的URL是用户跳转到登录界面的原地址
            
                        if (url == "null" || url == "" || typeof url == "undefined") url = "../../"; //如果没有就回首页
                        
                        setTimeout(function () { location.href = url; }, 1500);
                        break;
                    
                    case 1: //未知错误
                        showModal("未知错误: " + data.error_msg);
                        break;
                    
                    case 3: //用户名错误
                        showModal("登录失败，原因: 找不到该用户");
                        break;
                    
                    case 4: //密码错误
                        showModal("登录失败，原因: 密码错误");
                        break;
                    
                    case 5: //用户名长度过短
                        showModal("登录失败，原因: 用户名过短，长度必须在5~20位字符之间");
                        break;
        
                    case 6: //用户名长度过长
                        showModal("登录失败，原因: 用户名过长，长度必须在5~20位字符之间");
                        break;
        
                    case 7: //用户密码长度过短
                        showModal("登录失败，原因: 用户密码过短，长度必须在8~50位字符之间");
                        break;
        
                    case 8: //用户密码长度过长
                        showModal("登录失败，原因: 用户密码过长，长度必须在8~50位字符之间");
                        break;
                }
            },
            error: function (XHR) {
                showModal("请求失败，状态码: " + XHR.status);
            },
            timeout: 5000
        });
    });
  });