jQuery(document).ready(function ($) {
    jQuery("a.save_internal_comment").click(function (event) {
        event.preventDefault();

        var content = $(this).siblings('textarea').val();
        if ($.trim(content).length === 0) {
            alert('Field is empty');
            return;
        }

        // content is valid, add it to db
        var field = $(this).closest('.acf-field');
        field.block({
            message: null,
            overlayCSS: {backgroundColor: '#EFEFEF'}
        });

        $.ajax({
            method: 'POST',
            url: ajaxurl,
            data: {
                action: 'save_internal_comment_data',
                content: content,
                post_id: dws_get_param_by_name('post'),
                field_key: field.data('key')
            }
        }).done(function (comments) {
            field.find('.comments').html(comments);
            field.find('textarea').val('');
        }).fail(function (jqXHR, textStatus) {
            alert('ERROR: ' + textStatus);
        }).always(function () {
            field.unblock();
        });
    });
});
