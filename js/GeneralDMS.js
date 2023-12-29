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

async function deleteAllNotifications() {
    $('#notifications').html('<img style="position: fixed; top: 45%; left: 45%;" src="img/loading.gif" width="32" height="32">');

    $.get("app/ajax/Notifications.php", {
        action: "deleteAll"
    },
    async function(data) {
        $("#notifications").html("No notifications found");
        $("#notificationsController").html("Notifications (0)");
    });
}

function drawDocumentBulkActions() {
    var elems = $('#select:checked');

    if(elems.length > 0) {
        $('#bulk_actions').show();
        $('#bulk_actions').html('<img style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32">');

        var ids = [];

        elems.each(function(i) {
            ids[i] = this.value;
        });

        $.get('app/ajax/Documents.php', {
            idDocuments: ids,
            action: "getBulkActions"
        },
        async function(data) {
            await sleep(250);
            $('#bulk_actions').html(data);
        });
    } else {
        $('#bulk_actions').html('');
        $('#bulk_actions').hide();
    }
}

async function sendDocumentComment(id_author, id_document, can_delete) {
    var text = document.getElementById("text").value;

    if(text != "") {
        $("#cover").show();

        await sleep(500);

        $.ajax({
            url: 'app/ajax/Documents.php',
            type: 'POST',
            data: {
                commentText: text,
                idAuthor: id_author,
                idDocument: id_document,
                canDelete: can_delete,
                action: "sendComment"
            }
        })
        .done(async function(data) {
            await reloadDocumentComments(id_document, can_delete);
            document.getElementById("text").value = "";
            $('#cover').hide();
        });
    }
}

function showCommentsLoading() {
    $("#comments").append('<br><br><br><p style="text-align: center">Loading...</p>');
}

async function loadDocumentComments(id_document, can_delete, can_sleep = true) {
    if(can_sleep) {
        await sleep(500);
    }
    
    $.get("app/ajax/Documents.php", {
        idDocument: id_document,
        canDelete: can_delete,
        action: "getComments"
    },
    function(data) {
        $("#comments").empty();
        $('#comments').append(data);
    });
}

async function deleteDocumentComment(id_comment, id_document, can_delete) {
    await sleep(500);

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            idComment: id_comment,
            action: "deleteComment"
        }
    })
    .done(async function() {
        $('#comment' + id_comment).remove();

        await reloadDocumentComments(id_document, can_delete);
    });
}

async function reloadDocumentComments(id_document, can_delete) {
    $('#comments').empty();

    await loadDocumentComments(id_document, can_delete, false);
}

async function loadDocumentsSearchFilter(query, id_folder, _filter) {
    await sleep(500);

    $('#documents-loading').show();

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            q: query,
            idFolder: id_folder,
            filter: _filter,
            action: "search"
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}

async function loadDocumentsFilter(id_folder, _filter) {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            idFolder: id_folder,
            filter: _filter,
            action: "search"
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}

async function loadDocumentsSearch(query, id_folder) {
    if(query.length < 3 && query.length > 0) {
        return;
    } else if(query.length == 0) {
        await loadDocuments(id_folder);
    }

    await sleep(500);

    $('#documents-loading').show();

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            q: query,
            idFolder: id_folder,
            action: "search"
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}

async function loadDocuments(id_folder, _page) {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            idFolder: id_folder,
            action: "search",
            page: _page
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}

function selectAllDocumentEntries() {
    var selectAllElem = $('#select-all:checked').val();

    if(selectAllElem == "on") {
        $('#select:not(:checked)').prop('checked', true);
        drawDocumentBulkActions();
    } else {
        $('#select:checked').prop('checked', false);
        $('#bulk_actions').html('');
        $('#bulk_actions').hide();
    }
}

async function loadDocumentsSharedWithMe(_page) {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            action: "searchDocumentsSharedWithMe",
            page: _page
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}

async function loadProcesses(_page, _filter = 'waitingForMe') {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Processes.php',
        type: 'POST',
        data: {
            filter: _filter,
            action: "search",
            page: _page
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#processes-loading').hide();
    });
}

async function sendProcessComment(id_author, id_process, can_delete) {
    var text = document.getElementById("text").value;

    if(text != "") {
        $("#cover").show();

        await sleep(500);

        $.ajax({
            url: 'app/ajax/Processes.php',
            type: 'POST',
            data: {
                commentText: text,
                idAuthor: id_author,
                idProcess: id_process,
                canDelete: can_delete,
                action: "sendComment"
            }
        })
        .done(async function(data) {
            await reloadProcessComments(id_process, can_delete);
            document.getElementById("text").value = "";
            $('#cover').hide();
        });
    }
}

async function loadProcessComments(id_process, can_delete, can_sleep = true) {
    if(can_sleep) {
        await sleep(500);
    }
    
    $.get("app/ajax/Processes.php", {
        idProcess: id_process,
        canDelete: can_delete,
        action: "getComments"
    },
    function(data) {
        $("#comments").empty();
        $('#comments').append(data);
    });
}

async function deleteProcessComment(id_comment, id_process, can_delete) {
    await sleep(500);

    $.ajax({
        url: 'app/ajax/Processes.php',
        type: 'POST',
        data: {
            idComment: id_comment,
            action: "deleteComment"
        }
    })
    .done(async function() {
        $('#comment' + id_process).remove();

        await reloadProcessComments(id_process, can_delete);
    });
}

async function reloadProcessComments(id_process, can_delete) {
    $('#comments').empty();

    await loadProcessComments(id_process, can_delete, false);
}

function hideFlashMessage() {
    sessionStorage.removeItem('flash_message');
    $('#flash-message').hide();
}

async function loadMailQueue() {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Mails.php',
        type: 'GET',
        data: {
            action: "getQueue"
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#mailqueue-loading').hide();
    });
}

async function generateDocuments(_is_debug) {
    var _id_folder = $("#id_folder").val();
    var _count = $("#count").val();

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'GET',
        data: {
            action: "generateDocuments",
            id_folder: _id_folder,
            count: _count,
            is_debug: _is_debug
        }
    })
    .done(function(data) {
        $('#documents-loading').hide();

        if(_id_folder != "0") {
            //location.replace("?page=UserModule:Documents:showAll&id_folder=" + _id_folder);
            location.replace('?page=UserModule:AjaxHelper:flashMessage&id_folder=' + _id_folder + '&message=Documents%20have%20been%20generated&type=info&redirect=UserModule:Documents:showAll');
        } else {
            //location.replace("?page=UserModule:Documents:showAll");
            location.replace('?page=UserModule:AjaxHelper:flashMessage&message=Documents%20have%20been%20generated&type=info&redirect=UserModule:Documents:showAll');
        }
    });
}