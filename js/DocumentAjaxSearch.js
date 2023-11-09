async function ajaxSearch(query) {
    await sleep(250);

    //console.log(query);
    $.ajax({
        url: 'app/ajax/search.php',
        type: 'POST',
        data: {
            q: query
        }
    })
    .done(function(data) {
        $('table').html(data);
        //console.log(data);
    });
}