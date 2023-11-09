async function sendComment(id_author, id_document, can_delete) {
    var text = document.getElementById("text").value;

    if(text != "") {
        await sleep(500);

        $.ajax({
            url: 'app/send-comment.php',
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

function loadComments(id_document, can_delete) {
    $.get("app/get-comments.php", {
        idDocument: id_document,
        canDelete: can_delete
    },
    function(data) {
        $('#comments').append(data);
    });
}