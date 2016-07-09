jQuery(document).ready(function ($) {
    $('#dismiss-module-notice').on('click', function (event) {
        event.preventDefault();
        var data = {
            'action': 'dismiss_module_notice'
        };
        console.log($(this).closest('.is-dismissible').find('.notice-dismiss').trigger('click'));
        $(this).closest('.notice-dismiss').trigger('click');
        jQuery.post(ajax_object.ajax_url, data, function(response){

        });
    });
});
