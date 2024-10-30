(function($) {
    var last_select;

    function rename_radio()
    {
        $.each($('.js-file-select-div'), function(index)
        {
            $(this).find('input[type="radio"]').attr('name', 'js-file-selector-position-'+index);
        });
    }

    function refresh_state()
    {
        rename_radio();
        last_select = $('[name="gil_js_file_selector_file[]"]').last();

        if (last_select.val() == '') {
            //If no option selected, we can't add other select element
            $('.add-select-js').attr('disabled', 'true').removeClass('button-primary').addClass('button-default');
        }
        last_select.change(function()
        {
            if ($(this).val() != '') {
                $('.add-select-js').removeAttr('disabled').removeClass('button-default').addClass('button-primary');
            }
        });
    }

    if (typeof jQuery.fn.on === 'function') {
        $('body').on('change', '[name="gil_js_file_selector_file[]"]', function()
        {
            if ($(this).val() == '' && $('[name="gil_js_file_selector_file[]"]').length > 1) {
                $(this).parent().remove();
            }
            
            refresh_state();
        });
    } else {
        $('[name="gil_js_file_selector_file[]"]').live('change', function()
        {
            if ($(this).val() == '' && $('[name="gil_js_file_selector_file[]"]').length > 1) {
                $(this).parent().remove();
            }
            
            refresh_state();
        });
    }

    $('.add-select-js').click(function(e) {
        e.preventDefault();
        if (!e.target.disabled) {
            var clone = last_select.parent().clone();
            clone.find('select option').first().attr('selected', 'true');
            clone.find('input[type="radio"]').attr('name', 'js-file-selector-position-temp');
            last_select.parent().after(clone);
            clone.find('input[type="radio"]').first().attr('checked', 'true');
            refresh_state();
        }
    });

    refresh_state();
    $('#js-file-selector input[type="radio"][checked]').attr('checked', 'true');
}(jQuery));