function sendComment(id_author, id_document) {
    var text = document.getElementById("text").value;

    $.ajax({
        url: 'app/send-comment.php',
        type: 'POST',
        data: {
            commentText: text,
            idAuthor: id_author,
            idDocument: id_document
        }
    })
    .done(function(data) {
        $('#comments').prepend(data);
        document.getElementById("text").value = "";
        //console.log(data);
    })
}