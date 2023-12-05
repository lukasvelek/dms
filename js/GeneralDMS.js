function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

$(window).on("load", function() {
    $("#cover").remove();

    closeNotifications();
    loadNotificationCount();
});

async function openNotifications() {
    $("#notifications").show();
    $("#notificationsController").attr("onclick", "closeNotifications()");
    $('#notifications').html('<img style="position: fixed; top: 45%; left: 45%;" src="img/loading.gif" width="32" height="32">');

    $.get("app/ajax/Notifications.php", {
        action: "getNotifications"
    },
    async function(data) {
        let obj = JSON.parse(data);

        await sleep(250);

        $("#notifications").html(obj.code);
        $("#notificationsController").html("Notifications (" + obj.count  + ")");
    });
}

async function closeNotifications() {
    $("#notifications").hide();
    $("#notificationsController").attr("onclick", "openNotifications()");
    $("#notifications").html("");
}

async function useNotification(_id, _url) {
    $.get("app/ajax/Notifications.php", {
        id: _id,
        action: "hideNotification"
    }, async function() {
        location.href = _url;
    });
}

async function loadNotificationCount() {
    $.get("app/ajax/Notifications.php", {
        action: "loadCount"
    },
    async function(data) {
        $("#notificationsController").html("Notifications (" + data + ")");
    });
}