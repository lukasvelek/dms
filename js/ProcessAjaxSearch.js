async function ajaxLoadProcesses(_filter = 'waitingForMe') {
    await sleep(250);

    $.ajax({
        url: 'app/ajax/Processes.php',
        type: 'POST',
        data: {
            filter: _filter,
            action: "search"
        }
    })
    .done(function(data) {
        $('table').html(data);
        $('#processes-loading').hide();
    });
}