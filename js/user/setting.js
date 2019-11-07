$(function () {
    //通过生日获取年龄
    function getAge(bYear, bMonth, bDay) {
        var today = new Date();
        var tYear = today.getFullYear();
        var tMonth = today.getMonth();
        var tDay = today.getDate();

        var age = tYear -bYear; //获得岁数(未考虑月，日)

        //如果当月还没到生日月 or 如果当月就是生日月，且当天仍未到生日
        if ((tMonth < bMonth) || (tMonth == bMonth && tDay < bDay)) return --age;

        return age;
    }

    //通过生日获取星座
    function getConstellation(month, day) {
        var constellations = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    
        if (day <= 22) {
            if (month !== 1) {
                return constellations[month - 2];
            } else {
                return constellations[11];
            }
    
        } else {
            return constellations[month - 1];
        }
    }

    $(".date").datepicker({ //设置日期选择器
        format: "yyyy-mm-dd",
        language: "zh-CN",
        disableTouchKeyboard: "true",
        autoclose: "true",
        endDate: "0d",
        todayHighlight: "true",
        //defaultViewDate: $(".info-list .birthday > small").text() //默认打开生日那天
    });

    // 心情选择组件
    var id = undefined;
    $("input[type=radio]").change(function () {
        if (typeof id != "undefined") $("label[for=" + id + "]").css({"color": "#bdbdbd", "transform": "scale(1)"});
        switch ($(this).attr("id")) {
            case "happy": //喜
                id = "happy";
                $("label[for=happy]").css({"color": "#ffc107", "transform": "scale(1.15)"});
                break;
            case "angry": //怒
                id = "angry";
                $("label[for=angry]").css({"color": "#9c27b0", "transform": "scale(1.15)"});
                break;
            case "sad": //哀
                id = "sad";
                $("label[for=sad]").css({"color": "#2196f3", "transform": "scale(1.15)"});
                break;
            case "sohappy": //乐
                id = "sohappy";
                $("label[for=sohappy]").css({"color": "#f44336", "transform": "scale(1.15)"});
                break;
        }
    });

    function checkInput(input, minLength, maxLength) {
        var length = input.val().length; //输入框内容长度
        if (length < minLength || length > maxLength) { //不符合
            if (!input.hasClass("is-invalid")) input.addClass("is-invalid");
        } else {
            if (input.hasClass("is-invalid")) input.removeClass("is-invalid");
        }
    }

    function showTooltip(title) {
        var tooltip = $(".modal-header");
        tooltip.attr("data-original-title", title);
        tooltip.tooltip('show');
    }

    //即时验证
    $("#nickname, #email").keyup(function () { //禁止输入空格
        $(this).val($(this).val().replace(/\s+/g,""));
    });
    
    $("#nickname").on("input propertychange", function () { checkInput($(this), 5, 30); });
    var reg = /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/;
    $("#email").on("input propertychange", function () {
        if (reg.test($(this).val())) {
            if ($(this).hasClass("is-invalid")) $(this).removeClass("is-invalid");
        } else {
            if (!$(this).hasClass("is-invalid")) $(this).addClass("is-invalid");
        }
    });

    // 更新用户信息列表和用户信息表单的数据
    function updateUserInfo(data) {
        $(".user-card > .header .username").text(data.username); //用户名
        $(".user-card > .header .info > .uid").text("UID: " + data.uid); //uid

        //昵称
        $(".user-card > .header .info > .nickname").text("昵称: " + data.nickname);
        $("#nickname").val(data.nickname);

        //个性签名
        if (data.signature == null || data.signature == "") {
            $(".user-card > .footer").text("这个人很懒，什么都没留下……");
            $("#signature").val("");
        } else {
            $(".user-card > .footer").text(data.signature);
            $("#signature").val(data.signature);

        }

        //心情
        var Mood = function (className, color) { this.className = className; this.color = color; }
        var moods = [
            new Mood("fa-grin-alt", "#ffc107"), new Mood("fa-angry", "#9c27b0"), 
            new Mood("fa-frown", "#2196f3"),    new Mood("fa-grin-squint", "#f44336")
        ];
        var moodHTML = '<i class="fas ' + moods[0].className + '" style="font-size: 1.25rem; color: ' + moods[0].color + '"></i>'; //默认心情（喜）
        if (data.mood != null && data.mood <= moods.length) { //如果已经设置了正确的心情
            moodHTML = '<i class="fas ' + moods[data.mood - 1].className + '" style="font-size: 1.25rem; color: ' + moods[data.mood - 1].color + '"></i>'
            $("input[type=radio][value=" + data.mood + "]").change();
        } else {
            $("input[type=radio][value=1]").change();
        }
        $(".info-list .mood").html(moodHTML);

        //生日
        $(".info-list .birthday > small").text(data.birthday);
        $("#birthday").val(data.birthday);

        //性别
        var sex = function () {
            var sex = $("#sex");
            var sexList = ["保密", "男", "女"];
            if (data.sex >= 0 && data.sex <= 2) { //如果在0~2之内
                sex.val(data.sex);
                return sexList[data.sex];
            } else {
                sex.val(0);
                return sexList[0];
            }
        }
        $(".info-list .sex > small").text(sex);
        
        //年龄
        var birthday = new Date(data.birthday);
        var age = getAge(birthday.getFullYear(), birthday.getMonth(), birthday.getDate());
        $(".info-list .age > small").text(((age == 0) ? "不到1" : age) + "岁");

        //星座
        var Constellation = function (className, name, color) { this.className = className; this.name = name; this.color = color; }
        var constellations = [
            new Constellation("icon-shuipingzuo",  "水瓶座", "#b3e5fc"), new Constellation("icon-shuangyuzuo", "双鱼座", "#2196f3"),
            new Constellation("icon-muyangzuo",    "白羊座", "#f44336"), new Constellation("icon-jinniuzuo",   "金牛座", "#00bcd4"),
            new Constellation("icon-shuangzizuo",  "双子座", "#795548"), new Constellation("icon-juxiezuo",    "巨蟹座", "#9e9e9e"), 
            new Constellation("icon-shizizuo",     "狮子座", "#ffc107"), new Constellation("icon-chunvzuo",    "处女座", "#e91e63"),
            new Constellation("icon-tianchengzuo", "天秤座", "#4caf50"), new Constellation("icon-tianhezuo",   "天蝎座", "#616161"),
            new Constellation("icon-sheshouzuo",   "射手座", "#ff9800"), new Constellation("icon-mojiezuo",    "摩羯座", "#9c27b0")
        ];
        var constellation = constellations[data.constellation - 1];
        $(".info-list .constellation").html('\
            <i class="iconfont ' + constellation.className + '" style="font-size: 1.25rem; color: ' + constellation.color + ';"></i>\
            <small class="text-secondary font-weight-bold ml-1">' + constellation.name + '</small>\
        ');

        //邮箱
        if (data.email == null) {
            $(".info-list .email > small").text("未绑定");
            $("#email").val("");
        } else {
            $(".info-list .email > small").text(data.email);
            $("#email").val(data.email);
        }
    }

    $.get("./php/get-user-info.php", function (data) {
        if (data != false) {
            updateUserInfo(data); //填充用户信息先

            var oldData = new Map(); //存储旧信息
            oldData.set("nickname",  $("#nickname").val());
            oldData.set("signature", $("#signature").val());
            oldData.set("mood",      $("input[name=mood]:checked").val());
            oldData.set("birthday",  $("#birthday").val());
            oldData.set("sex",       $("#sex").val());
            oldData.set("email",     $("#email").val());

            $("#user-info-setting .btn-submit").click(function () {
                if ($("input, textarea").hasClass("is-invalid")) {
                    showTooltip("请先将表单填写正确！");
                    return false;
                }

                if ($.trim($("#nickname").val()) == "" || $.trim($("#birthday").val()) == "") {
                    showTooltip("请先将昵称 或 生日填写完整！");
                    return false;
                }
                
                var newData = new Map(); //存储新信息
                newData.set("nickname",  $("#nickname").val());
                newData.set("signature", $("#signature").val());
                newData.set("mood",      $("input[name=mood]:checked").val());
                newData.set("birthday",  $("#birthday").val());
                newData.set("sex",       $("#sex").val());
                newData.set("email",     $("#email").val());

                var formData = new FormData();
                newData.forEach(function (value, key) {
                    if (value != oldData.get(key)) { //如果当前值不等于旧的值，代表有数据更新
                        formData.append(key, value);

                        if (key == "birthday") {
                            var birthday = new Date(value);
                            formData.append("constellation", getConstellation(birthday.getMonth() + 1, birthday.getDate()));
                        }
                    }
                });

                if (!(formData.has("nickname") || formData.has("signature") || formData.has("mood") || formData.has("birthday") || formData.has("sex") || formData.has("email"))) {
                    showTooltip("然而你并没有更新任何数据……");
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: "./user/action/setting.php",
                    dataType: "JSON",
                    data: formData,
                    cache: false, //防止ie8之前版本缓存get请求的处理方式
                    processData: false,
                    contentType: false,
                    complete: function (XHR, TS) { //刷新缓存数据
                        oldData = newData;
                        $.get("./php/get-user-info.php", function (data) { updateUserInfo(data); }, "json");
                    },
                    success: function (data) {
                        switch (data) {
                            case true:
                                showTooltip("用户信息更新成功！");
                                break;

                            case false:
                                showTooltip("服务器拒绝处理该请求！");
                                break;

                            case "nickname":
                                showTooltip("昵称长度不合格！");
                                break;

                            case "signature":
                                showTooltip("个性签名长度不合格！");
                                break;

                            case "mood":
                                showTooltip("心情代号错误！");
                                break;

                            case "birthday":
                                showTooltip("生日时间格式错误！");
                                break;

                            case "sex":
                                showTooltip("性别代号错误！");
                                break;

                            case "constellation":
                                showTooltip("十二星座代号错误！");
                                break;

                            case "email":
                                showTooltip("电子邮箱格式错误！");
                                break;

                            default:
                                showTooltip("未知错误！");
                                break;
                        }
                    },
                    error: function (XHR) {
                        showTooltip("请求失败，状态码: " + XHR.status);
                    },
                    timeout: 5000
                });
            });
        }
    }, "json");

});