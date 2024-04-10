$('#date_from').on('change', function() {
    $('#date_to').attr('min', this.value);
});