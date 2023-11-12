function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

$(window).on("load", function() {
    $("#cover").hide();
});