var limit_range = document.getElementById('limit_range');
var limit_text = document.getElementById('limit_text');

if(limit_range.value == 1) {
    limit_text.innerHTML = "" + limit_range.value + " document";
} else if(limit_range.value == limit_range.getAttribute('max')) {
    limit_text.innerHTML = "All";
} else {
    limit_text.innerHTML = "" + limit_range.value + " documents";
}


limit_range.oninput = function() {
    if(limit_range.value == 1) {
        limit_text.innerHTML = "" + limit_range.value + " document";
    } else if(limit_range.value == limit_range.getAttribute('max')) {
        limit_text.innerHTML = "All";
    } else {
        limit_text.innerHTML = "" + limit_range.value + " documents";
    }
};