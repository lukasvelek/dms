if($('#date_from').val() != '') {
    $('#date_to').attr('min', $('#date_from').val());
}

$('#date_from').on('change', function() {
    $('#date_to').attr('min', this.value);
});