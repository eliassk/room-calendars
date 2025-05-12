jQuery(function($){
    $('#rc-add-row').on('click', function(e){
        e.preventDefault();
        $('#rc-calendar-table tbody').append(
            '<tr><td><input name="room_calendars_data[name][]" /></td>'
            +'<td><input name="room_calendars_data[url][]" style="width:100%;" /></td>'
            +'<td><button class="rc-remove-row button">-</button></td></tr>'
        );
    });
    $(document).on('click','.rc-remove-row',function(e){
        e.preventDefault();
        $(this).closest('tr').remove();
    });
});