async function ajaxLoadProcesses(_filter = 'waitingForMe') {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/process-search.php',
        type: 'POST',
        data: {
            filter: _filter
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#processes-loading').hide();
    });
}