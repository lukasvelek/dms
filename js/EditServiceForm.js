var text = document.getElementById('files_keep_length_text_value');
var range = document.getElementById('files_keep_length');

if(range.value == 1) {
    text.innerHTML = range.value + " day";
} else {
    text.innerHTML = range.value + " days";
}

range.oninput = function() {
    if(range.value == 1) {
        text.innerHTML = range.value + " day";
    } else {
        text.innerHTML = range.value + " days";
    }
};