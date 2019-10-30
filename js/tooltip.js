$(function () {
    var tooltip = $('[data-toggle="tooltip"]');
    tooltip.tooltip({
        trigger: "hover"
    });

    tooltip.on("shown.bs.tooltip", function () {
        var the = $(this);
        setTimeout(() => { //显示tooltip的3秒后自动消失
            the.tooltip("hide");
        }, 2500);
    })
});