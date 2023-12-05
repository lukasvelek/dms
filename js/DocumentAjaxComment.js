async function sendComment(id_author, id_document, can_delete) {
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
            await reloadComments(id_document, can_delete);
            document.getElementById("text").value = "";
            $('#cover').hide();
        });
    }
}

function showLoading() {
    $("#comments").append('<br><br><br><p style="text-align: center">Loading...</p>');
}

async function loadComments(id_document, can_delete, canSleep = true) {
    if(canSleep) {
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

async function deleteComment(id_comment, id_document, can_delete) {
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

        await reloadComments(id_document, can_delete);
    });
}

async function reloadComments(id_document, can_delete) {
    $('#comments').empty();

    await loadComments(id_document, can_delete, false);
}