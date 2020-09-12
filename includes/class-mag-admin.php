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

		$magento_stores_url = $rest_api_url['scheme'] . '://' . $rest_api_url['host'];
		$magento_stores_url .= preg_replace( '/\/api\/rest\/?/', '', $rest_api_url['path'] );
		$magento_stores_url .= MAG_PRODUCTS_INTEGRATION_MODULE_STORES_PATH;

		$response = wp_remote_get(
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
		$rest_api_url = parse_url( get_option( 'mag_products_integration_rest_api_url' ) );
		$random_code = wp_generate_password( 16, false, false );
		$magento_module_url = $rest_api_url['scheme'] . '://' . $rest_api_url['host'] . preg_replace( '/\/api\/rest\/?/', '', $rest_api_url['path'] ) . MAG_PRODUCTS_INTEGRATION_MODULE_VERIFY_INSTALLATION_PATH . '/code/' . $random_code;
		$response = wp_remote_get(
			$magento_module_url, array(
				'timeout' => 10,
			)
		);
		$json_data = array();
		$json_response = json_decode( $response['body'] );
		if ( $json_response && ! $response instanceof \WP_Error && is_array( $response ) && 200 == $response['response']['code'] ) {
			if ( $json_response->code == $random_code ) {
				update_option( 'mag_products_integration_magento_module_installed', 1 );
				$json_data = array(
					'installed' => 1,
					'message' => __( 'Magento module installation successfully verified.', 'mag-products-integration' ),
				);
			}
		} else {
			$json_data = array(
				'installed' => 0,
				'message' => __( 'Unable to verify the Magento module installation. Make sure to <strong>Flush Magento Cache</strong>!', 'mag-products-integration' ),
			);
			update_option( 'mag_products_integration_default_store_code', '' );
		}
		wp_send_json( $json_data );
		wp_die();
	}

	/**
	 * Enqueue AJAX JavaScript script for the plugin admin page.
	 *
	 * @param string $hook Hook executed which allow us to target a specific admin page.
	 *
	 * @since 1.0.0
	 *
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
		register_setting( 'mag_products_integration', 'mag_products_integration_selected_store_class' );
		if ( magepress_store_manager()->has_store() ) {
			magepress_store_manager()->get_store()->register_settings();
		}
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
	 * Render the admin configuration page
	 *
	 * @since 1.0.0
	 */
	public function page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Magento Settings', 'mag-products-integration' ); ?></h2>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'mag_products_integration' ); ?>
				<?php do_settings_sections( 'mag_products_integration' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="now"><?php _e( 'Select your store', 'mag-products-integration' ); ?></th>
						<td>
							<select name="mag_products_integration_selected_store_class">
								<option value=""></option>
								<?php foreach ( magepress_store_manager()->get_available_stores() as $store_class => $label ): ?>
									<option <?php selected( $store_class, get_option( 'mag_products_integration_selected_store_class' ) ) ?> value="<?php esc_attr_e( $store_class ) ?>"><?php esc_html_e( $label ) ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<?php if ( magepress_store_manager()->has_store() ): ?>
						<tr valign="top">
							<td colspan="2" style="background: #fcfcfc;">
								<?php magepress_store_manager()->get_store()->admin_page(); ?>
							</td>
						</tr>
					<?php endif; ?>
				</table>
				<p class="submit">
					<?php submit_button( null, 'primary', 'submit', false ); ?>
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
	 * Tells if the jquery script is enabled or disabled
	 *
	 * @return bool
	 * @deprecated Since 1.2.7. The script has been replaced with flex-box.
	 *
	 * @since      1.2.0
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
			//add_action( 'admin_notices', array( $this, 'notify_plugin_not_ready' ) );
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
		ob_start();
		readfile( plugin_dir_path( dirname( __FILE__ ) ) . 'images/icon.svg' );
		$icon = ob_get_clean();

		add_menu_page(
			__( 'Magento', 'mag-products-integration' ),
			__( 'Magento', 'mag-products-integration' ),
			'manage_options',
			__FILE__,
			array( Mag::get_instance()->get_admin(), 'page' ),
			'data:image/svg+xml;base64,' . base64_encode( $icon )
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
				'default' => '#3399cc',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport' => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_regular_price', array(
				'default' => '#858585',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport' => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_button', array(
				'default' => '#3399cc',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport' => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_button_text', array(
				'default' => '#FFFFFF',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport' => 'postMessage',
			)
		);

		$wp_customize->add_setting(
			'magento_color_button_hover', array(
				'default' => '#2e8ab8',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport' => 'postMessage',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_current_price',
				array(
					'label' => __( 'Current Price', 'mag-products-integration' ),
					'settings' => 'magento_color_current_price',
					'section' => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_regular_price',
				array(
					'label' => __( 'Regular Price', 'mag-products-integration' ),
					'settings' => 'magento_color_regular_price',
					'section' => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_button', array(
					'label' => __( 'Button', 'mag-products-integration' ),
					'settings' => 'magento_color_button',
					'section' => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_button_text', array(
					'label' => __( 'Button text', 'mag-products-integration' ),
					'settings' => 'magento_color_button_text',
					'section' => 'magento_settings_colors',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize, 'magento_color_button_hover', array(
					'label' => __( 'Button hover', 'mag-products-integration' ),
					'settings' => 'magento_color_button_hover',
					'section' => 'magento_settings_colors',
				)
			)
		);
	}
}
