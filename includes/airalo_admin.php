<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';

use Airalo\Admin\AiraloOrder;
use Airalo\Admin\OrderValidator;
use Airalo\Helpers\Cached;

add_action( 'admin_enqueue_scripts', 'airalo_enqueue_admin_styles' );

function airalo_enqueue_admin_styles() {
    wp_enqueue_style(
        'airalo-reset-style',
        plugin_dir_url( __FILE__ ) . '../assets/css/resetStyle.css',
        [],
        AIRALO_PLUGIN_VERSION
    );

    // Enqueue main plugin styles
    wp_enqueue_style(
        'airalo-plugin-style',
        plugin_dir_url( __FILE__ ) . '../assets/css/pluginStyle.css',
        [],
        AIRALO_PLUGIN_VERSION
    );
}

add_action( 'admin_menu', 'airalo_menu' );

function airalo_menu () {
	add_menu_page(
		'Airalo Plugin Settings',
		'Airalo',
		'manage_options',
		'airalo-settings',
		'airalo_settings_page'
	);
}

function airalo_settings_page () {
	$credentials = new \Airalo\Admin\Settings\Credential();
	$client_id = $credentials->get_credential( \Airalo\Admin\Settings\Credential::CLIENT_ID );
	$sandbox_client_id = $credentials->get_credential( \Airalo\Admin\Settings\Credential::CLIENT_ID_SANDBOX );

	$options = new \Airalo\Admin\Settings\Option();

	$client_secret_encrypted = $options->fetch_option(\Airalo\Admin\Settings\Credential::CLIENT_SECRET);
	$client_sandbox_secret_encrypted = $options->fetch_option(\Airalo\Admin\Settings\Credential::CLIENT_SECRET_SANDBOX);

	$auto_publish = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Option::AUTO_PUBLISH );
	$auto_publish_after_update = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Option::AUTO_PUBLISH_AFTER_UPDATE );
	$use_sandbox = $options->fetch_option_for_settings_page(\Airalo\Admin\Settings\Option::USE_SANDBOX);
	$airalo_sim_name = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Option::USE_AIRALO_SIM_NAME );

	$sync_images = $options->fetch_option( \Airalo\Admin\Settings\Option::SYNC_IMAGES );
	if ( 'off' !== $sync_images ) {
		$sync_images = 'checked';
	} else {
		$sync_images = '';
	}

	$last_sync = $options->fetch_option(\Airalo\Admin\Settings\Option::LAST_SYNC);
	$last_successful_sync = $options->fetch_option(\Airalo\Admin\Settings\Option::LAST_SUCCESSFUL_SYNC);

	$error = $options->fetch_option( \Airalo\Admin\Settings\Option::SYNC_ERROR );
	$show_error = $error ? 'airaloShow': 'airaloHide';

	$language = $options->fetch_option( \Airalo\Admin\Settings\Option::LANGUAGE );

	wp_enqueue_style('airalo-admin-style', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap');

	$nonce = wp_create_nonce( 'airalo-admin' );

	?>

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

	<div id="airalo-container">
		<div class="airaloPluginHeader">
			<h2 class="airaloPluginTitle"> Airalo Plugin</h2>
			<div class="airaloLogoContainer">
				<?php require plugin_dir_path( __FILE__ ) . '../assets/images/logo-partner-platform.svg'; ?>
			</div>
		</div>

		<div class="cardsContainer">
			<section>
				<div class="airaloCard">
					<p class="cardTitle">General</p>

					<div class="flexBox">
						<div>
							<h3 class="actionName">Sync Products</h3>
							<div>
								<span class="airaloChip">Last Sync: <?php echo esc_html( $last_sync ); ?></span>
							</div>
							<div>
								<span class="airaloChip">Last Successful Sync: <?php echo esc_html( $last_successful_sync ); ?></span>
							</div>
							<div class="<?php echo esc_html( $show_error ); ?>">
								<span class="airaloChip">Sync Error:<?php echo esc_html( $error ); ?></span>
							</div>
						</div>
						<form method="post" action="options.php">
							<?php settings_fields('airalo-settings-group'); ?>
							<?php do_settings_sections('airalo-settings-group'); ?>

							<input type="submit" name="sync_products" value="Sync Now" class="airaloButton"/>
						</form>
					</div>
				</div>
			</section>
			<form method="post" action="options.php">
				<?php settings_fields('airalo-settings-group'); ?>
				<?php do_settings_sections('airalo-settings-group'); ?>
				<input type="hidden" name="airalo_admin_nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<section>
					<div class="airaloCard settingsCard">
						<p class="cardTitle">Settings</p>

							<div style="margin-bottom: 10px">
								<label>
									<select name="airalo_language">
										<option disabled selected value> -- Select A Language -- </option>

										<?php

										foreach ( \Airalo\Admin\Settings\Language::get_all_languages() as $key => $value ) {

											$selected = $language == $key ? 'selected' : '';

											echo '<option value=' . esc_html( $key ) . ' ' . esc_html( $selected ) . '>' . esc_html( $value ) . '</option>';
										}

										?>
									</select>
								</label>
							</div>
							<div>
								<label for="airalo_use_sandbox">
									Use Sandbox
									<span class="switch">
									<input type="checkbox" name="airalo_use_sandbox" <?php echo esc_html( $use_sandbox ); ?> id="airalo_use_sandbox"/>
									<span class="slider round"></span>
									</span>
								</label>
							</div>
							<div>
								<label for="airalo_sync_images">
									Sync Images
									<span class="switch">
										<input type="checkbox" name="airalo_sync_images" <?php echo esc_html(  $sync_images ); ?> id="airalo_sync_images"/>
										<span class="slider round"></span>
										</span>
								</label>
							</div>
						<div>
							<label for="airalo_sim_name">
								Use Airalo Sim Name
								<span class="switch">
										<input type="checkbox" name="airalo_sim_name" <?php echo esc_html( $airalo_sim_name ); ?> id="airalo_sim_name"/>
										<span class="slider round"></span>
										</span>
							</label>
						</div>
							<div>
								<label for="airalo_auto_publish">
									Auto Publish Product
									<span class="switch">
									<input type="checkbox" name="airalo_auto_publish" <?php echo esc_html( $auto_publish ); ?> id="airalo_auto_publish"/>
									<span class="slider round"></span>
									</span>
								</label>
							</div>
							<div>
								<label for="airalo_auto_publish_update">
									Auto Publish After Price Update
									<span class="switch">
									<input type="checkbox" name="airalo_auto_publish_update" <?php echo esc_html( $auto_publish_after_update ); ?> id="airalo_auto_publish_update"/>
									<span class="slider round"></span>
									</span>
								</label>
							</div>

					</div>

				</section>

				<section>
					<div class="airaloCard credentialsCard">
						<p class="cardTitle">Production Credentials</p>
							<div>
								<label for="airalo_client_id_sandbox">Client Id</label>
								<input type="text" name="airalo_client_id" placeholder="Enter ID" value="<?php echo esc_html( $client_id ); ?>"/>
							</div>


							<div>
								<label for="airalo_client_secret_sandbox">Client Secret</label>
								<input type="password" name="airalo_client_secret" placeholder="Enter Secret"  value="<?php echo esc_html( $client_secret_encrypted ); ?>" />
							</div>
					</div>

					<div class="airaloCard credentialsCard">
						<p class="cardTitle">Sandbox Credentials</p>

							<div>
								<label for="airalo_client_id_sandbox">Client Id</label>
								<input type="text" name="airalo_client_id_sandbox" placeholder="Enter ID" value="<?php echo esc_html( $sandbox_client_id ); ?>"/>
							</div>

							<div>
								<label for="airalo_client_secret_sandbox">Client Secret</label>
								<input type="password" name="airalo_client_secret_sandbox" placeholder="Enter Secret"  value="<?php echo esc_html( $client_sandbox_secret_encrypted ); ?>" />
							</div>

						<div class="airaloButtonContainer">
							<input type="submit" name="save_airalo_settings" value="Save Settings" class="airaloButton"/>
						</div>
					</div>
				</section>
			</form>
		</div>
	</div>

	<?php
}

add_action( 'admin_init', 'airalo_register_settings' );

function airalo_register_settings () {
	register_setting( 'airalo-settings-group', 'airalo_settings' );

	$options = new \Airalo\Admin\Settings\Option();

	add_settings_section(
		'airalo_main_section',
		'Main Settings',
		'airalo_main_section_db',
		'airalo'
	);

	add_settings_field(
		'airalo_setting_field',
		'Example Setting',
		'airalo_settings_field_cb',
		'airalo',
		'my_custom_plugin_main_section'
	);

	if ( isset( $_POST['sync_products'] ) ) {
		do_action( 'airalo_sync_products' );
	}

	if ( isset ( $_POST['save_airalo_settings'] ) ) {

		if ( ! isset( $_POST['airalo_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['airalo_admin_nonce'] ), 'airalo-admin' ) ) {
			return;
		}

		airalo_save_settings();

		$client_id = isset ( $_POST['airalo_client_id'] ) ? sanitize_text_field( $_POST['airalo_client_id'] ) : null;
		$client_secret = isset ( $_POST['airalo_client_secret'] ) ? sanitize_text_field( $_POST['airalo_client_secret'] ) : null;
		$encrypted_secret = $options->fetch_option( \Airalo\Admin\Settings\Credential::CLIENT_SECRET );

		if ( $client_secret == $encrypted_secret && null != $encrypted_secret ) {
			$client_secret = null;
		}

		airalo_save_credentials(  $client_id, $client_secret );

		$sandbox_client_id = isset( $_POST['airalo_client_id_sandbox'] ) ? sanitize_text_field( $_POST['airalo_client_id_sandbox'] ): null;
		$sandbox_client_secret = isset( $_POST['airalo_client_secret_sandbox'] ) ? sanitize_text_field( $_POST['airalo_client_secret_sandbox'] ): null;

		$encrypted_sandbox_secret = $options->fetch_option( \Airalo\Admin\Settings\Credential::CLIENT_SECRET_SANDBOX );

		if ( $sandbox_client_secret == $encrypted_sandbox_secret && null != $encrypted_sandbox_secret ) {
			$sandbox_client_secret = null;
		}

		airalo_save_credentials( $sandbox_client_id, $sandbox_client_secret, true );
	}
}

function airalo_save_settings(): void {
	$auto_publish = isset( $_POST['airalo_auto_publish'] ) ? sanitize_text_field( $_POST['airalo_auto_publish'] ) : 'off';
	$auto_publish_after_update = isset( $_POST['airalo_auto_publish_update'] ) ? sanitize_text_field( $_POST['airalo_auto_publish_update'] ) : 'off';
	$use_sandbox = isset( $_POST['airalo_use_sandbox'] ) ? sanitize_text_field( $_POST['airalo_use_sandbox'] ) : 'off';
	$language = isset( $_POST['airalo_language'] ) ? sanitize_text_field( $_POST['airalo_language'] ) : 'en';
	$sync_images = isset( $_POST['airalo_sync_images'] ) ? sanitize_text_field( $_POST['airalo_sync_images'] ) : 'off';
	$airalo_sim_name = isset( $_POST['airalo_sim_name'] ) ? sanitize_text_field( $_POST['airalo_sim_name'] ): 'off';

	$options = new \Airalo\Admin\Settings\Option();

	$use_sandbox_old = $options->fetch_option( \Airalo\Admin\Settings\Option::USE_SANDBOX );
	if ( $use_sandbox_old != $use_sandbox ) {
		$options->insert_option( \Airalo\Admin\Settings\Option::ENVIRONMENT_SWITCHED, 'true' );
	}

	$options->insert_option( \Airalo\Admin\Settings\Option::AUTO_PUBLISH, $auto_publish );
	$options->insert_option( \Airalo\Admin\Settings\Option::AUTO_PUBLISH_AFTER_UPDATE, $auto_publish_after_update );
	$options->insert_option( \Airalo\Admin\Settings\Option::USE_SANDBOX, $use_sandbox );
	$options->insert_option( \Airalo\Admin\Settings\Option::LANGUAGE, $language );
	$options->insert_option( \Airalo\Admin\Settings\Option::SYNC_IMAGES, $sync_images );
	$options->insert_option( \Airalo\Admin\Settings\Option::USE_AIRALO_SIM_NAME, $airalo_sim_name );
}

function airalo_save_credentials( $clientId, $clientSecret, $isSandbox = false ): void {
	$clientIdCredential = \Airalo\Admin\Settings\Credential::CLIENT_ID;
	$clientSecretCredential = \Airalo\Admin\Settings\Credential::CLIENT_SECRET;

	if ( $isSandbox ) {
		$clientIdCredential = \Airalo\Admin\Settings\Credential::CLIENT_ID_SANDBOX;
		$clientSecretCredential = \Airalo\Admin\Settings\Credential::CLIENT_SECRET_SANDBOX;
	}

	$credentials = new \Airalo\Admin\Settings\Credential();

	if ( $clientId ) {
		$credentials->insert_credential( sanitize_text_field( $clientId ), $clientIdCredential );
	}

	if ( $clientSecret ) {
		$credentials->insert_credential( sanitize_text_field( $clientSecret ), $clientSecretCredential );
	}
}

function airalo_main_section_db() {
	echo '<p>Settings</p>';
}

function airalo_settings_field_cb() {
	$options = get_options( 'airalo-options' );
	echo '<p>Settings</p>';
}

add_action( 'airalo_sync_products', 'airalo_sync_products_function', 10, 2 );

function airalo_sync_products_function() {
	$product_syncer = new \Airalo\Admin\Syncers\ProductSyncer();
	$product_syncer->handle();
}

add_filter( 'woocommerce_add_to_cart_validation', 'airalo_validate_cart_item_quantity', 10, 3 );

function airalo_validate_cart_item_quantity( $passed, $ignore_param, $quantity ) {
	return ( new OrderValidator() )->handle( $passed, $quantity );
}

add_action( 'woocommerce_thankyou', 'airalo_submit_order', 10, 1 );

function airalo_submit_order( $order ) {
	$order = wc_get_order( $order );

	if ( Cached::get( function() {
		// do nothing, just check if cache for this order exists
	}, $order->get_id() )
	) {
		return;
	}

	$items = $order->get_items();
	$order_items = new \Airalo\Admin\OrderItem( $items );
	$airalo_order_items = $order_items->get_airalo_order_items();

	// skip if no airalo order items
	if ( empty( $airalo_order_items ) ) {
		return;
	}

	( new AiraloOrder() )->handle( $order );
}

add_action( 'woocommerce_order_status_completed', 'airalo_admin_order_on_status' );
add_action( 'woocommerce_order_status_processing', 'airalo_admin_order_on_status' );

function airalo_admin_order_on_status( $order_id ) {
	$order = wc_get_order( $order_id );

	$created_via = $order->get_created_via();

	if ( $created_via != 'admin' ) {
		return;
	}

	if ( Cached::get( function () {
		// do nothing, just check if cache for this order exists
	}, $order->get_id() )
	) {
		return;
	}

	( new AiraloOrder() )->handle( $order );
}
