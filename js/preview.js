(function ($) {
    var api = wp.customize;

    wp.customize('magento_color_button', function (value) {
        value.bind(function (to) {
            $('.magento-wrapper ul.products li.product .url a')
                .css('background-color', to)
                .hover(function () {
                    $(this).css('background-color', api.instance('magento_color_button_hover').get());
                }, function () {
                    $(this).css('background-color', to);
                });
        });
    });

    wp.customize('magento_color_button_hover', function (value) {
        value.bind(function (to) {
            $('.magento-wrapper ul.products li.product .url a').hover(function () {
                $(this).css('background-color', to);
            }, function () {
                $(this).css('background-color', api.instance('magento_color_button').get());
            });
        });
    });

    wp.customize('magento_color_current_price', function (value) {
        value.bind(function (to, from) {
            $('.magento-wrapper ul.products li.product .price .current-price').css('color', to);
        });
    });

    wp.customize('magento_color_regular_price', function (value) {
        value.bind(function (to, from) {
            $('.magento-wrapper ul.products li.product .price .regular-price').css('color', to);
        });
    });

    wp.customize('magento_color_button_text', function (value) {
        value.bind(function (to, from) {
            $('.magento-wrapper ul.products li.product .url a').css('color', to);
        });
    });
})(jQuery);
