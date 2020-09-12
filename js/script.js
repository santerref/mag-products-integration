jQuery(document).ready(function ($) {
	$('body').on('click', '#verify-magento-module', function (event) {
		event.preventDefault();
		$(this).append('<div class="spinner" style="margin: 0 0 0 10px; float: inherit; visibility: visible;"></div>');
		var data = {
			'action': 'verify_magento_module_installation'
		};
		$.post(ajax_object.ajax_url, data, function (response) {
			if (!response.installed) {
				$('.error.notice.unable').remove();
				$('#verify-magento-module .spinner').remove();
				$('.wrap > h2').after('<div class="error notice unable"><p>' + response.message + '</p></div>');
			} else {
				var success = '<div class="updated notice"><p>' + response.message + '</p></div>';
				data = {
					'action': 'get_available_stores',
					'referer': $('#verify-magento-module').closest('form').find('input[name="_wp_http_referer"]').val()
				};
				$.post(ajax_object.ajax_url, data, function (response) {
					$('.spinner').hide();
					$('#wpbody-content > .wrap').html(response.html);
					$('.wrap > h2').after(success);
				});
			}
		});
	}).on('click', 'input[name="mag_products_integration_cache_enabled"]', function () {
		if ($(this).is(':checked')) {
			$('.cache-lifetime').show();
		} else {
			$('.cache-lifetime').hide();
		}
	}).on('click', '#dismiss-module-notice', function (event) {
		event.preventDefault();
		var data = {
			'action': 'dismiss_module_notice'
		};
		$(this).closest('.notice-dismiss').trigger('click');
		$.post(ajax_object.ajax_url, data, function (response) {

		});
	}).on('click', '[data-flush-cache]', function (event) {
		event.preventDefault();
		$(this).after('<div class="spinner" style="margin: 0 0 0 5px; float: inherit; visibility: visible;padding-bottom: 4px;"></div>');
		var data = {
			'action': 'flush_cache'
		};
		$.post(ajax_object.ajax_url, data, function (response) {
			$('.spinner').hide();
			$('.notice.flush-cache').remove();
			var success = '<div class="updated notice notice-success is-dismissible flush-cache"><p>' + response.message + '</p></div>';
			var appended = false;
			$('body, html').animate({scrollTop: 0}, 200, function () {
				if (!appended) {
					$('.wrap > h2').after(success);
					$(document).trigger('wp-updates-notice-added');
					appended = true;
				}
			});
		});
	}).on('change', 'select[name="mag_products_integration_selected_store_class"]', function (event) {
		var data = {
			store_class: $(this).val(),
			action: 'get_store_form'
		};
		$('#store-form').parents('fieldset').attr('disabled', 'disabled');
		$('.loading-gif').show();
		$.get(ajax_object.ajax_url, data, function (response) {
			$('#store-form').html(response);
		}).always(function () {
			$('.loading-gif').hide();
			$('#store-form').parents('fieldset').removeAttr('disabled');
		});
	});
});
