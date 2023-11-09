async function sendComment(id_author, id_document, can_delete) {
    var text = document.getElementById("text").value;

    if(text != "") {
        await sleep(500);

        $.ajax({
            url: 'app/ajax/send-comment.php',
            type: 'POST',
            data: {
                commentText: text,
                idAuthor: id_author,
                idDocument: id_document,
                canDelete: can_delete
            }
        })
        .done(function(data) {
            $('#comments').prepend(data);
            document.getElementById("text").value = "";
            //console.log(data);
        });
    }
}

async function loadComments(id_document, can_delete) {
    await sleep(500);

    $.get("app/ajax/get-comments.php", {
        idDocument: id_document,
        canDelete: can_delete
    },
    function(data) {
        $('#comments').append(data);
    });
}

async function deleteComment(id_comment) {
    await sleep(500);

    $.ajax({
        url: 'app/ajax/delete-comment.php',
        type: 'POST',
        data: {
            idComment: id_comment
        }
    })
    .done(function() {
        $('#comment' + id_comment).remove();
    });
}