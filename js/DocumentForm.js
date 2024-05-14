$('#file').on('change', function() {
    var value = $('#file').val();

    $('#name').attr('value', value);

    if(value != '') {
        $('#name').attr('readonly', true);
    } else {
        $('#name').removeAttr('readonly');
    }
});