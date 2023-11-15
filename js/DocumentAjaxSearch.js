async function ajaxSearch(query, id_folder) {
    await sleep(/*250*/ 500);

    $('#documents-loading').show();

    $.ajax({
        url: 'app/ajax/search.php',
        type: 'POST',
        data: {
            q: query,
            idFolder: id_folder
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}

async function ajaxLoadDocuments(id_folder) {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/search.php',
        type: 'POST',
        data: {
            idFolder: id_folder
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}