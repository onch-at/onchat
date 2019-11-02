$(function () {
    var register = $(".register-btn");

    function showModal(text) {
        $(".modal-body").html(text);
        $(".modal").modal("show");
    };
    
    function updateCaptcha() {
        $("#captcha").val("");
        $(".captcha-img").attr("src", "../../images/captcha/index.php?" + Date.now());
    }
    
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
  
    $("#password").on("input propertychange", function () {
        checkInput($(this), 8, 50);
    
        //判断密码是否一致
        var input = $("#confirm-password");
    
        checkInput(input, 8, 50);
    
        //如果密码长度符合
        if (!input.hasClass("is-invalid")) {
            var feedback = $(".confirm-password-feedback");
    
            if (input.val() !== $(this).val()) { //不一致
                if (feedback.text() == " 密码长度必须在8~50位字符之间！") feedback.html('<i class="fas fa-exclamation-triangle"></i> 确认密码与密码不一致！');
        
                if (!input.hasClass("is-invalid")) input.addClass("is-invalid");
            } else {
                if (feedback.text() == " 确认密码与密码不一致！") feedback.html('<i class="fas fa-exclamation-triangle"></i> 密码长度必须在8~50位字符之间！');
        
                if (input.hasClass("is-invalid")) input.removeClass("is-invalid");
            }
        }
    });
    
    $("#confirm-password").on("input propertychange", function () {
        var input = $(this);
    
        checkInput(input, 8, 50);
    
        //如果密码长度符合
        if (!input.hasClass("is-invalid")) {
            var feedback = $(".confirm-password-feedback");
    
            if (input.val() !== $("#password").val()) { //不一致
                if (feedback.text() == " 密码长度必须在8~50位字符之间！") feedback.html('<i class="fas fa-exclamation-triangle"></i> 确认密码与密码不一致！');
    
                if (!input.hasClass("is-invalid")) input.addClass("is-invalid");
            } else {
                if (feedback.text() == " 确认密码与密码不一致！") feedback.html('<i class="fas fa-exclamation-triangle"></i> 密码长度必须在8~50位字符之间！');
    
                if (input.hasClass("is-invalid")) input.removeClass("is-invalid");
            }
        }
    });
  
    $("#captcha").on("input propertychange", function () { checkInput($(this), 4, 4); });
  
    //点击验证码更换验证码
    $(".captcha-img").click(function () { updateCaptcha(); });
    
    //提交表单处理
    register.click(function () {
        if (!checkForm()) {
            updateCaptcha();
            showModal("请先将表单填写完整！");
            return false;
        }
    
        if ($("input").hasClass("is-invalid")) {
            updateCaptcha();
            showModal("请先将表单填写正确！");
            return false;
        }
        
        if ($("#password").val() !== $("#confirm-password").val()) {
            updateCaptcha();
            showModal("注册失败，原因: 密码与确认密码不一致");
            return false;
        }
        
        if ($("#captcha").val().length !== 4) {
            updateCaptcha();
            showModal("验证码填写错误！");
            return false;
        }
        
        $.ajax({
            type: "POST",
            url: "../action/register.php",
            dataType: "JSON",
            data: {
            username: $("#username").val(),
            password: $("#password").val(),
            captcha: $("#captcha").val(),
            },
            beforeSend: function (XHR) {
                register.text("正在注册...");
            },
            complete: function (XHR, TS) {
            if (register.text() !== "立即注册") register.text("立即注册");
            },
            success: function (data) {
                if (data.status_code !== 0) updateCaptcha(); //如果不成功就刷新验证码
                switch(data.status_code) {
                    case -1: //验证码错误
                        showModal("验证码填写错误！");
                        break;
                
                    case 0: //成功
                        showModal("注册成功，已自动为您登录，准备跳转...");
                        setTimeout(function () { location.href="../../"; }, 1500);
                        break;
                    
                    case 1: //未知错误
                        showModal("未知错误: " + data.error_msg);
                        break;
                    
                    case 2: //用户名重复
                        showModal("注册失败，原因: 用户名已被占用");
                        break;
                    
                    case 5: //用户名长度过短
                        showModal("注册失败，原因: 用户名过短，长度必须在5~20位字符之间");
                        break;
                    
                    case 6: //用户名长度过长
                        showModal("注册失败，原因: 用户名过长，长度必须在5~20位字符之间");
                        break;
                    
                    case 7: //用户密码长度过短
                        showModal("注册失败，原因: 用户密码过短，长度必须在8~50位字符之间");
                        break;
                    
                    case 8: //用户密码长度过长
                        showModal("注册失败，原因: 用户密码过长，长度必须在8~50位字符之间");
                        break;
                }
            },
            error: function (XHR) {
                updateCaptcha();
                showModal("请求失败，状态码: " + XHR.status);
            },
            timeout: 5000
        });
        
    });
    
  });