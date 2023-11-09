function ajaxSearch(query) {
    //console.log(query);
    $.ajax({
        url: 'app/search.php',
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