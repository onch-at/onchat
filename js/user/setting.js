$(function () {
    $(".date").datepicker({ //设置日期选择器
        format: "yyyy-mm-dd",
        language: "zh-CN",
        disableTouchKeyboard: "true",
        autoclose: "true",
        endDate: "0d",
        todayHighlight: "true",
        defaultViewDate: $(".info-list .birthday > small").text() //默认打开生日那天
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

    $("#nickname").on("input propertychange", function () { checkInput($(this), 5, 30); });
    $("#signature, #email").on("input propertychange", function () { checkInput($(this), 0, 50); });
});