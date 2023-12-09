async function ajaxSearch(query, id_folder) {
    await sleep(/*250*/ 500);

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

async function ajaxLoadDocuments(id_folder) {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            idFolder: id_folder,
            action: "search"
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
        drawBulkActions();
    } else {
        $('#select:checked').prop('checked', false);
        $('#bulk_actions').html('');
        $('#bulk_actions').hide();
    }
}

async function ajaxLoadDocumentsSharedWithMe() {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Documents.php',
        type: 'POST',
        data: {
            action: "searchDocumentsSharedWithMe"
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#documents-loading').hide();
    });
}