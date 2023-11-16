async function sendComment(id_author, id_process, can_delete) {
    var text = document.getElementById("text").value;

    if(text != "") {
        $("#cover").show();

        await sleep(500);

        $.ajax({
            url: 'app/ajax/process-send-comment.php',
            type: 'POST',
            data: {
                commentText: text,
                idAuthor: id_author,
                idProcess: id_process,
                canDelete: can_delete
            }
        })
        .done(async function(data) {
            await reloadComments(id_process, can_delete);
            document.getElementById("text").value = "";
            $('#cover').hide();
        });
    }
}

function showLoading() {
    $("#comments").append('<br><br><br><p style="text-align: center">Loading...</p>');
}

async function loadComments(id_process, can_delete, canSleep = true) {
    if(canSleep) {
        await sleep(500);
    }
    
    $.get("app/ajax/process-get-comments.php", {
        idProcess: id_process,
        canDelete: can_delete
    },
    function(data) {
        $("#comments").empty();
        $('#comments').append(data);
    });
}

async function deleteComment(id_comment, id_process, can_delete) {
    await sleep(500);

    $.ajax({
        url: 'app/ajax/process-delete-comment.php',
        type: 'POST',
        data: {
            idComment: id_comment
        }
    })
    .done(async function() {
        $('#comment' + id_process).remove();

        await reloadComments(id_process, can_delete);
    });
}

async function reloadComments(id_process, can_delete) {
    $('#comments').empty();

    await loadComments(id_process, can_delete, false);
}