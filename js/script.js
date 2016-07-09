jQuery(document).ready(function ($) {
    $('#verify-magento-module').on('click', function (event) {
        event.preventDefault();
        $(this).append('<div class="spinner" style="margin: 0 0 0 10px; float: inherit; visibility: visible;"></div>');
        var data = {
            'action': 'verify_magento_module_installation'
        };
        jQuery.post(ajax_object.ajax_url, data, function (response) {
            if (!response.installed) {
                $('.error.notice.unable').remove();
                $('#verify-magento-module .spinner').remove();
                $('.wrap > h2').after('<div class="error notice unable"><p>' + response.message + '</p></div>');
            } else {
                var success = '<div class="updated notice"><p>' + response.message + '</p></div>';
                data = {
                    'action': 'get_available_stores',
                    'referer': jQuery('#verify-magento-module').closest('form').find('input[name="_wp_http_referer"]').val()
                };
                jQuery.post(ajax_object.ajax_url, data, function (response) {
                    $('.spinner').hide();
                    $('#wpbody-content > .wrap').html(response.html);
                    $('.wrap > h2').after(success);
                });
            }
        });
    });

    $('#dismiss-module-notice').on('click', function (event) {
        event.preventDefault();
        var data = {
            'action': 'dismiss_module_notice'
        };
        $(this).closest('.notice-dismiss').trigger('click');
        jQuery.post(ajax_object.ajax_url, data, function (response) {

        });
    });

    $('input[name="flush-cache"]').on('click', function (event) {
        event.preventDefault();
        $('p.submit').append('<div class="spinner" style="margin: 0 0 0 5px; float: inherit; visibility: visible;padding-bottom: 4px;"></div>');
        var data = {
            'action': 'flush_cache'
        };
        jQuery.post(ajax_object.ajax_url, data, function (response) {
            $('.spinner').hide();
            $('.notice.flush-cache').remove();
            var success = '<div class="updated notice flush-cache"><p>' + response.message + '</p></div>';
            var appended = false;
            $('body, html').animate({scrollTop: 0}, 200, function () {
                if (!appended) {
                    $('.wrap > h2').after(success);
                    appended = true;
                }
            });
        });
    });
});
