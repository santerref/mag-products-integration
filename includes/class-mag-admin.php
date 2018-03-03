<?php
/**
 * Administration page definition for settings and options.
 *
 * @package Mag_Products_Integration
 */

namespace MagePress;

/**
 * Class Mag_Admin
 *
 * @since   1.0.0
 */
class Mag_Admin {

	/**
	 * This function is executed in the admin area.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_ajax_script' ) );
		add_action(
			'wp_ajax_verify_magento_module_installation', array(
				$this,
				'verify_magento_module_installation',
			)
		);
		add_action( 'wp_ajax_get_available_stores', array( $this, 'get_available_stores' ) );
		add_action( 'wp_ajax_dismiss_module_notice', array( $this, 'dismiss_module_notice' ) );
		add_action( 'wp_ajax_flush_cache', array( $this, 'flush_cache' ) );
		$this->verify_settings();
	}

	/**
	 * Dismiss missing Magento module notice shown on every page.
	 *
	 * @since 1.2.0
	 */
	public function dismiss_module_notice() {
		update_option( 'mag_products_integration_dismiss_module_notice', true );
	}

	/**
	 * Flush cache storage (transient)
	 *
	 * @since 1.2.2
	 */
	public function flush_cache() {
		Mag::get_instance()->get_cache()->force_update_cache();

		wp_send_json(
			array(
				'message' => __( 'The cache storage has been flushed.', 'mag-products-integration' ),
			)
		);
		wp_die();
	}

	/**
	 * AJAX function executed to get all Magento's store codes.
	 *
	 * Output response in JSON.
	 *
	 * @since 1.0.0
	 */
	public function get_available_stores() {
		$rest_api_url = parse_url(
			get_option( 'mag_products_integration_rest_api_url' )
		);

		$magento_stores_url  = $rest_api_url['scheme'] . '://' . $rest_api_url['host'];
		$magento_stores_url .= preg_replace( '/\/api\/rest\/?/', '', $rest_api_url['path'] );
		$magento_stores_url .= MAG_PRODUCTS_INTEGRATION_MODULE_STORES_PATH;

		$response      = wp_remote_get(
			$magento_stores_url, array(
				'timeout' => 10,
			)
		);
		$json_response = json_decode( $response['body'] );

		update_option( 'mag_products_integration_stores_code', serialize( $json_response->stores ) );
		update_option( 'mag_products_integration_default_store_code', $json_response->default_store );

		ob_start();
		$this->page();
		$html = ob_get_clean();

		$html = preg_replace( '#name="_wp_http_referer" value="([^"]+)"#i', 'name="_wp_http_referer" value="' . esc_html( $_POST['referer'] ) . '"', $html );

		$json_data = array(
			'html' => $html,
		);

		wp_send_json( $json_data );
		wp_die();
	}

	/**
	 * AJAX function executed to verify if the Magento module is installed.
	 *
	 * Output response in JSON.
	 *
	 * @since 1.0.0
	 */
	public function verify_magento_module_installation() {
		update_option( 'mag_products_integration_dismiss_module_notice', false );
		$rest_api_url       = parse_url( get_option( 'mag_products_integration_rest_api_url' ) );
		$random_code        = wp_generate_password( 16, false, false );
		$magento_module_url = $rest_api_url['scheme'] . '://' . $rest_api_url['host'] . preg_replace( '/\/api\/rest\/?/', '', $rest_api_url['path'] ) . MAG_PRODUCTS_INTEGRATION_MODULE_VERIFY_INSTALLATION_PATH . '/code/' . $random_code;
		$response           = wp_remote_get(
			$magento_module_url, array(
				'timeout' => 10,
			)
		);
		$json_data          = array();
		$json_response      = json_decode( $response['body'] );
		if ( $json_response && ! $response instanceof \WP_Error && is_array( $response ) && 200 == $response['response']['code'] ) {
			if ( $json_response->code == $random_code ) {
				update_option( 'mag_products_integration_magento_module_installed', 1 );
				$json_data = array(
					'installed' => 1,
					'message'   => __( 'Magento module installation successfully verified.', 'mag-products-integration' ),
				);
			}
		} else {
			$json_data = array(
				'installed' => 0,
				'message'   => __( 'Unable to verify the Magento module installation. Make sure to <strong>Flush Magento Cache</strong>!', 'mag-products-integration' ),
			);
			update_option( 'mag_products_integration_default_store_code', '' );
		}
		wp_send_json( $json_data );
		wp_die();
	}

	/**
	 * Enqueue AJAX JavaScript script for the plugin admin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Hook executed which allow us to target a specific admin page.
	 */
	public function load_ajax_script( $hook ) {
		wp_enqueue_script( 'ajax-notice', plugins_url( '/js/notice.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		if ( preg_match( '/^toplevel_page_mag-products-integration/i', $hook ) ) {
			wp_enqueue_script( 'ajax-script', plugins_url( '/js/script.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		}
		wp_localize_script(
			'ajax-notice', 'ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
		wp_localize_script(
			'ajax-script', 'ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Register settings for the plugin admin page.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'mag_products_integration', 'mag_products_integration_rest_api_url', array(
				$this,
				'validate_rest_api_url',
			)
		);
		register_setting(
			'mag_products_integration', 'mag_products_integration_cache_enabled', array(
				$this,
				'validate_cache_enabled',
			)
		);
		register_setting(
			'mag_products_integration', 'mag_products_integration_cache_lifetime', array(
				$this,
				'validate_cache_lifetime',
			)
		);
		register_setting( 'mag_products_integration', 'mag_products_integration_disable_customizer_css' );
	}

	/**
	 * Validate the checkbox value for the "enable cache" option.
	 *
	 * @param int $cache_enabled The value of the checkbox on the admin page.
	 *
	 * @return bool Whether or not the cached is enabled by the user.
	 */
	public function validate_cache_enabled( $cache_enabled ) {
		if ( empty( $cache_enabled ) ) {
			Mag::get_instance()->get_cache()->force_update_cache();
		}

		return $cache_enabled;
	}

	/**
	 * Live preview color picker JS script.
	 *
	 * @since 1.2.7
	 */
	public function customize_preview() {
		wp_enqueue_script(
			'mag-products-integration-preview',
			plugins_url( '/js/preview.min.js', dirname( __FILE__ ) ),
			array( 'customize-preview', 'jquery' )
		);
	}

	/**
	 * Make sure that the lifetime is not altered.
	 *
	 * If the selected lifetime is different from the current, update to expire option value.
	 *
	 * @since 1.2.0
	 *
	 * @param int $mag_products_integration_cache_lifetime Lifetime choosen by the user.
	 *
	 * @return string Validated lifetime.
	 */
	public function validate_cache_lifetime( $mag_products_integration_cache_lifetime ) {
		$valid_values = array(
			HOUR_IN_SECONDS,
			6 * HOUR_IN_SECONDS,
			12 * HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			3 * DAY_IN_SECONDS,
			WEEK_IN_SECONDS,
			YEAR_IN_SECONDS,
		);

		$current_lifetime = Mag::get_instance()->get_cache()->get_lifetime();
		if ( $mag_products_integration_cache_lifetime != $current_lifetime ) {
			Mag::get_instance()->get_cache()->update_expiration( time() + $mag_products_integration_cache_lifetime );
		}

		if ( ! in_array( $mag_products_integration_cache_lifetime, $valid_values ) ) {
			$mag_products_integration_cache_lifetime = Mag_Cache::DEFAULT_CACHE_LIFETIME;
		}

		return $mag_products_integration_cache_lifetime;
	}

	/**
	 * Validate the syntax of the Magento REST API URL and verify if it's a valid API endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mag_products_integration_rest_api_url URL of the Magento REST API endpoint.
	 *
	 * @return string The URL if everything is fine, empty string otherwise.
	 */
	public function validate_rest_api_url( $mag_products_integration_rest_api_url ) {
		$valid = false;
		if ( get_option( 'mag_products_integration_rest_api_url_validated' ) && get_option( 'mag_products_integration_rest_api_url', null ) == $mag_products_integration_rest_api_url ) {
			return $mag_products_integration_rest_api_url;
		} else {
			update_option( 'mag_products_integration_rest_api_url_validated', 0 );
			update_option( 'mag_products_integration_magento_module_installed', 0 );
		}
		if ( ! filter_var( $mag_products_integration_rest_api_url, FILTER_VALIDATE_URL ) ) {
			add_settings_error(
				'mag_products_integration', 'mag_products_integration_rest_api_url',
				/* translators: REST API URL typed by the user. */
				sprintf( __( 'The URL "%s" is invalid.', 'mag-products-integration' ), $mag_products_integration_rest_api_url )
			);

			return '';
		}

		if ( ! preg_match( '/api\.php\?type=rest/i', $mag_products_integration_rest_api_url ) ) {
			$mag_products_integration_rest_api_url = trailingslashit( $mag_products_integration_rest_api_url );
		}

		$response = wp_remote_get(
			$mag_products_integration_rest_api_url, array(
				'headers' => array(
					'Accept' => 'application/json',
				),
				'timeout' => 10,
			)
		);

		if ( $response instanceof \WP_Error ) {
			add_settings_error(
				'mag_products_integration', 'mag_products_integration_rest_api_url',
				_n(
					'Something went wrong while the plugin was trying to connect to the API. Please verify the error below.',
					'Something went wrong while the plugin was trying to connect to the API. Please verify the errors below.',
					count( $response->errors ), 'mag-products-integration'
				)
			);
			foreach ( $response->get_error_messages() as $error ) {
				add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', $error );
			}
		} elseif ( is_array( $response ) && ! empty( $response['body'] ) ) {
			$decoded_array = json_decode( $response['body'], true );
			if ( null !== $decoded_array ) {
				$valid = true;
				add_settings_error(
					'mag_products_integration', 'mag_products_integration_rest_api_url',
					__( 'The API URL has been successfully validated.', 'mag-products-integration' ), 'updated'
				);
				update_option( 'mag_products_integration_default_store_code', '' );
			} else {
				if ( preg_match( '/api\/rest\/$/i', $mag_products_integration_rest_api_url ) ) {
					$mag_products_integration_rest_api_url_alternative = str_replace(
						'api/rest/', 'api.php?type=rest',
						$mag_products_integration_rest_api_url
					);

					$response = wp_remote_get(
						$mag_products_integration_rest_api_url_alternative, array(
							'headers' => array(
								'Accept' => 'application/json',
							),
							'timeout' => 10,
						)
					);

					if ( is_array( $response ) && ! empty( $response['body'] ) ) {
						$decoded_array = json_decode( $response['body'], true );
						if ( null !== $decoded_array ) {
							$valid                                 = true;
							$mag_products_integration_rest_api_url = '';
							add_settings_error(
								'mag_products_integration',
								'mag_products_integration_rest_api_url',
								sprintf( /* translators: Link to "How to enable rewrite" documentation page. */
									__(
										'The REST API is enabled but the URL rewrite is not working. Read more here: %s',
										'mag-products-integration'
									),
									'<a target="_blank" href="http://magentowp.santerref.com/htaccess.html">http://magentowp.santerref.com/htaccess.html</a>'
								)
							);
						}
					}
				}

				if ( ! $valid ) {
					$mag_products_integration_rest_api_url = '';
					add_settings_error(
						'mag_products_integration', 'mag_products_integration_rest_api_url',
						__( 'The URL is not a valid API endpoint.', 'mag-products-integration' )
					);
				}
			}
		} else {
			add_settings_error(
				'mag_products_integration', 'mag_products_integration_rest_api_url',
				__( 'The URL is not a valid API endpoint.', 'mag-products-integration' )
			);
		}

		update_option( 'mag_products_integration_rest_api_url_validated', intval( $valid ) );

		return $mag_products_integration_rest_api_url;
	}

	/**
	 * Render the admin configuration page
	 *
	 * @since 1.0.0
	 */
	public function page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Magento Settings', 'mag-products-integration' ); ?></h2>
			<?php settings_errors(); ?>

			<p>
				<?php _e( 'You have to <strong>enable REST API</strong> first in your Magento store and <strong>give the product API Resources</strong> to your Guest role. Otherwise, it will be impossible to retreive your products.', 'mag-products-integration' ); ?>
			</p>

			<p style="color: #b50000; font-weight: bold;"><?php _e( 'Magento module is optional. If you are not using it, make sure to use the cache to reduce page load time.', 'mag-products-integration' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'mag_products_integration' ); ?>
				<?php do_settings_sections( 'mag_products_integration' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="now"><?php _e( 'Disable Customizer CSS', 'mag-products-integration' ); ?></th>
						<td>
							<input type="checkbox" name="mag_products_integration_disable_customizer_css"<?php echo get_option( 'mag_products_integration_disable_customizer_css', false ) ? ' checked' : ''; ?> />
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Magento REST API URL', 'mag-products-integration' ); ?></th>
						<td><input type="text" class="regular-text" name="mag_products_integration_rest_api_url" value="<?php echo esc_attr( get_option( 'mag_products_integration_rest_api_url' ) ); ?>"/>

							<p class="description"><?php _e( 'Do not forget to <strong>put the trailing slash</strong>. Ex: http://yourmagentostore.com/api/rest/', 'mag-products-integration' ); ?></p>
						</td>
					</tr>

					<?php if ( Mag::get_instance()->is_ready() && ! Mag::get_instance()->is_module_installed() ) : ?>
						<tr valign="top">
							<th scope="row"></th>
							<td>
								<a href="#" id="verify-magento-module"><?php _e( 'Verify Magento module installation and get available stores', 'mag-products-integration' ); ?>&#8594;</a>
							</td>
						</tr>
					<?php elseif ( Mag::get_instance()->is_ready() && Mag::get_instance()->is_module_installed() ) : ?>
						<tr valign="top">
							<th scope="row"><?php _e( 'Magento module installed', 'mag-products-integration' ); ?></th>
							<td><?php _e( 'Yes', 'mag-products-integration' ); ?></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Available stores code', 'mag-products-integration' ); ?></th>
							<td><?php echo esc_html( implode( ', ', unserialize( get_option( 'mag_products_integration_stores_code', array() ) ) ) ); ?></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Default store code', 'mag-products-integration' ); ?></th>
							<td><?php echo esc_html( get_option( 'mag_products_integration_default_store_code', '' ) ); ?></td>
						</tr>
					<?php endif; ?>

					<?php if ( Mag::get_instance()->is_ready() ) : ?>
						<tr valign="top">
							<th scope="now"><?php _e( 'Enable cache', 'mag-products-integration' ); ?></th>
							<td>
								<input type="checkbox" name="mag_products_integration_cache_enabled"<?php echo Mag::get_instance()->get_cache()->is_enabled() ? ' checked' : ''; ?> />
							</td>
						</tr>

						<tr valign="top"
							class="cache-lifetime"
							<?php
							if ( ! Mag::get_instance()->get_cache()->is_enabled() ) :
								?>
								style="display: none;"<?php endif; ?>>
							<th scope="now"><?php _e( 'Cache lifetime', 'mag-products-integration' ); ?></th>
							<td>
								<?php
								$this->display_cache_lifetime_html(
									get_option(
										'mag_products_integration_cache_lifetime',
										Mag_Cache::DEFAULT_CACHE_LIFETIME
									)
								);
								?>
							</td>
						</tr>
					<?php endif; ?>
				</table>

				<p class="submit">
					<?php submit_button( null, 'primary', 'submit', false ); ?>
					<?php submit_button( __( 'Flush cache', 'mag-products-integration' ), 'secondary', 'flush-cache', false ); ?>
				</p>
			</form>
			<p>
				<?php
				_e(
					'For developers: <a target="_blank" href="http://magentowp.santerref.com/documentation.html"><strong>actions</strong> and <strong>filters</strong> documentation</a>.',
					'mag-products-integration'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display <select> for cache lifetime
	 *
	 * @since 1.2.0
	 *
	 * @param int $default_lifetime Default lifetime to be selected.
	 */
	protected function display_cache_lifetime_html( $default_lifetime = Mag_Cache::DEFAULT_CACHE_LIFETIME ) {
		// Compatibility with 1.2.1.
		if ( 'indefinite' == $default_lifetime ) {
			$default_lifetime = YEAR_IN_SECONDS;
		}
		$options = array(
			array(
				'lifetime' => HOUR_IN_SECONDS,
				'label'    => __( '1 hour', 'mag-products-integration' ),
			),
			array(
				'lifetime' => 6 * HOUR_IN_SECONDS,
				'label'    => __( '6 hours', 'mag-products-integration' ),
			),
			array(
				'lifetime' => 12 * HOUR_IN_SECONDS,
				'label'    => __( '12 hours', 'mag-products-integration' ),
			),
			array(
				'lifetime' => DAY_IN_SECONDS,
				'label'    => __( '1 day', 'mag-products-integration' ),
			),
			array(
				'lifetime' => 3 * DAY_IN_SECONDS,
				'label'    => __( '3 days', 'mag-products-integration' ),
			),
			array(
				'lifetime' => WEEK_IN_SECONDS,
				'label'    => __( '1 week', 'mag-products-integration' ),
			),
			array(
				'lifetime' => YEAR_IN_SECONDS,
				'label'    => __( '1 year', 'mag-products-integration' ),
			),
		);

		$html = '<select name="mag_products_integration_cache_lifetime">';
		foreach ( $options as $option ) {
			$html .= '<option value="' . $option['lifetime'] . '"';
			if ( $option['lifetime'] == $default_lifetime ) {
				$html .= ' selected';
			}
			$html .= '>' . $option['label'] . '</option>';
		}
		$html .= '</select>';

		echo $html;
	}

	/**
	 * Tells if the jquery script is enabled or disabled
	 *
	 * @since      1.2.0
	 * @deprecated Since 1.2.7. The script has been replaced with flex-box.
	 *
	 * @return bool
	 */
	public function use_jquery_script() {
		return get_option( 'mag_products_integration_jquery_script', true );
	}

	/**
	 * Show the Plugin not configured notice.
	 *
	 * @since 1.0.0
	 */
	public function notify_plugin_not_ready() {
		?>
		<div class="error notice is-dismissible">
			<p>
				<?php
				echo sprintf(
					/* translators: Link to the plugin's configuration page. */
					__( 'Please <a href="%s">configure Magento plugin</a> before using the shortcode.', 'mag-products-integration' ),
					admin_url( 'admin.php?page=mag-products-integration%2Fincludes%2Fclass-mag-admin.php' )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show the Magento module not installed notice.
	 *
	 * @since 1.0.0
	 */
	public function notify_magento_module_not_verified() {
		?>
		<div class="error notice is-dismissible">
			<p>
				<?php
				_e(
					'Please verify Magento module installation and load available stores. <a id="dismiss-module-notice" href="#">Dismiss this notice, I am not going to use the Magento module.</a>',
					'mag-products-integration'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show notices if the plugin is not ready or the magento module not installed.
	 *
	 * @since 1.0.0
	 */
	public function verify_settings() {
		if ( ! Mag::get_instance()->is_ready() ) {
			add_action( 'admin_notices', array( $this, 'notify_plugin_not_ready' ) );
		} elseif ( ! Mag::get_instance()->is_module_installed() ) {
			$dismiss_module_notice = get_option( 'mag_products_integration_dismiss_module_notice', false );
			if ( ! $dismiss_module_notice ) {
				add_action( 'admin_notices', array( $this, 'notify_magento_module_not_verified' ) );
			}
		}
	}

	/**
	 * Create new Magento admin menu.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'Magento', 'mag-products-integration' ),
			__( 'Magento', 'mag-products-integration' ),
			'manage_options',
			__FILE__,
			array( Mag::get_instance()->get_admin(), 'page' ),
			plugins_url( 'images/icon-16x16.png', dirname( __FILE__ ) )
		);
	}

	/**
	 * Register new customizer settings.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer instance to add panels, sections and settings.
	 *
	 * @since 1.2.7
	 */
	public function customize_register( $wp_customize ) {
		$wp_customize->add_panel(
			'magento_settings', array(
				'title' => __( 'Magento', 'mag-products-integration' ),
			)
		);

		$wp_customize->add_section(
			'magento_settings_colors', array(
				'title' => __( 'Colors', 'mag-products-integration' ),
				'panel' => 'magento_settings',
			)
		);

		$wp_customize->add_setting(
			'magento_color_current_price', array(
				'default'           => '#3399cc',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_regular_price', array(
				'default'           => '#858585',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_button', array(
				'default'           => '#3399cc',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_button_text', array(
				'default'           => '#FFFFFF',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_button_hover', array(
				'default'           => '#2e8ab8',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_current_price',
				array(
					'label'    => __( 'Current Price', 'mag-products-integration' ),
					'settings' => 'magento_color_current_price',
					'section'  => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_regular_price',
				array(
					'label'    => __( 'Regular Price', 'mag-products-integration' ),
					'settings' => 'magento_color_regular_price',
					'section'  => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_button', array(
					'label'    => __( 'Button', 'mag-products-integration' ),
					'settings' => 'magento_color_button',
					'section'  => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_button_text', array(
					'label'    => __( 'Button text', 'mag-products-integration' ),
					'settings' => 'magento_color_button_text',
					'section'  => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_button_hover', array(
					'label'    => __( 'Button hover', 'mag-products-integration' ),
					'settings' => 'magento_color_button_hover',
					'section'  => 'magento_settings_colors',
				)
			)
		);
	}
}
