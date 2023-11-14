function drawBulkActions() {
    var elems = $('#select:checked');

    if(elems.length > 0) {
        $('#bulk_actions').show();
        $('#bulk_actions').html('<img style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32">');

        var ids = [];

        elems.each(function(i) {
            ids[i] = this.value;
        });

        $.get('app/ajax/bulk-actions.php', {
            idDocuments: ids
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