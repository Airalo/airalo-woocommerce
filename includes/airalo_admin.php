<?php

require_once plugin_dir_path( __FILE__ ). '../vendor/autoload.php';

use Airalo\Admin\AiraloOrder;
use Airalo\Admin\OrderValidator;
use Airalo\Helpers\Cached;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
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

    $sync_images = $options->fetch_option( \Airalo\Admin\Settings\Option::SYNC_IMAGES );
    if ( $sync_images !== 'off' ) {
        $sync_images = 'checked';
    } else {
        $sync_images = '';
    }

    $last_sync = $options->fetch_option(\Airalo\Admin\Settings\Option::LAST_SYNC);
    $last_successful_sync = $options->fetch_option(\Airalo\Admin\Settings\Option::LAST_SUCCESSFUL_SYNC);

    $error = $options->fetch_option(\Airalo\Admin\Settings\Option::SYNC_ERROR);    
    $show_error = $error ? 'airaloShow': 'airaloHide';

    $language = $options->fetch_option( \Airalo\Admin\Settings\Option::LANGUAGE );

    ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <style><?php require plugin_dir_path( __FILE__ ) . '../assets/css/resetStyle.css' ?></style>
    <style><?php require plugin_dir_path( __FILE__ ) . '../assets/css/pluginStyle.css' ?></style>

    <div id="airalo-container">
        <div class="airaloPluginHeader">
            <h2 class="airaloPluginTitle"> Airalo Plugin</h2>
            <div class="airaloLogoContainer">
                <?php require plugin_dir_path( __FILE__ ) . '../assets/images/logo-partner-platform.svg' ?>
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
                                <span class="airaloChip">Last Sync: <?php echo $last_sync ?></span>
                            </div>
                            <div>
                                <span class="airaloChip">Last Successful Sync: <?php echo $last_successful_sync  ?></span>
                            </div>
                            <div class="<?php echo $show_error ?>">
                                <span class="airaloChip">Sync Error:<?php echo $error?></span>
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

                                            echo "<option value=" . $key ." " . $selected .">" . $value . "</option>";
                                        }

                                        ?>
                                    </select>
                                </label>
                            </div>
                            <div>
                                <label for="airalo_use_sandbox">
                                    Use Sandbox
                                    <span class="switch">
                                    <input type="checkbox" name="airalo_use_sandbox" <?php echo $use_sandbox ?> id="airalo_use_sandbox"/>
                                    <span class="slider round"></span>
                                    </span>
                                </label>
                            </div>
                            <div>
                                <label for="airalo_sync_images">
                                    Sync Images
                                    <span class="switch">
                                        <input type="checkbox" name="airalo_sync_images" <?php echo $sync_images ?> id="airalo_sync_images"/>
                                        <span class="slider round"></span>
                                        </span>
                                </label>
                            </div>
                            <div>
                                <label for="airalo_auto_publish">
                                    Auto Publish Product
                                    <span class="switch">
                                    <input type="checkbox" name="airalo_auto_publish" <?php echo $auto_publish ?> id="airalo_auto_publish"/>
                                    <span class="slider round"></span>
                                    </span>
                                </label>
                            </div>
                            <div>
                                <label for="airalo_auto_publish_update">
                                    Auto Publish After Price Update
                                    <span class="switch">
                                    <input type="checkbox" name="airalo_auto_publish_update" <?php echo $auto_publish_after_update ?> id="airalo_auto_publish_update"/>
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
                                <input type="text" name="airalo_client_id" placeholder="Enter ID" value="<?php echo htmlspecialchars($client_id, ENT_QUOTES, 'UTF-8') ?>"/>
                            </div>


                            <div>
                                <label for="airalo_client_secret_sandbox">Client Secret</label>
                                <input type="password" name="airalo_client_secret" placeholder="Enter Secret"  value="<?php echo htmlspecialchars($client_secret_encrypted, ENT_QUOTES, 'UTF-8') ?>" />
                            </div>
                    </div>

                    <div class="airaloCard credentialsCard">
                        <p class="cardTitle">Sandbox Credentials</p>

                            <div>
                                <label for="airalo_client_id_sandbox">Client Id</label>
                                <input type="text" name="airalo_client_id_sandbox" placeholder="Enter ID" value="<?php echo htmlspecialchars($sandbox_client_id, ENT_QUOTES, 'UTF-8') ?>"/>
                            </div>

                            <div>
                                <label for="airalo_client_secret_sandbox">Client Secret</label>
                                <input type="password" name="airalo_client_secret_sandbox" placeholder="Enter Secret"  value="<?php echo htmlspecialchars($client_sandbox_secret_encrypted, ENT_QUOTES, 'UTF-8') ?>" />
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
        'my_custom_plugin_main_section',
    );

    if ( isset( $_POST['sync_products'] ) ) {
        do_action( 'sync_products' );
    }

    if ( isset ( $_POST['save_airalo_settings'] ) ) {
        save_airalo_settings();

        $client_id = $_POST['airalo_client_id'] ?? null;
        $client_secret = $_POST['airalo_client_secret'] ?? null;
        $encrypted_secret = $options->fetch_option( \Airalo\Admin\Settings\Credential::CLIENT_SECRET );

        if  ($client_secret == $encrypted_secret && $encrypted_secret != null ) {
            $client_secret = null;
        }

        save_airalo_credentials(  $client_id, $client_secret );

        $sandbox_client_id = $_POST['airalo_client_id_sandbox'] ?? null;
        $sandbox_client_secret = $_POST['airalo_client_secret_sandbox'] ?? null;

        $encrypted_sandbox_secret = $options->fetch_option( \Airalo\Admin\Settings\Credential::CLIENT_SECRET_SANDBOX );

        if ($sandbox_client_secret == $encrypted_sandbox_secret && $encrypted_sandbox_secret != null) {
            $sandbox_client_secret = null;
        }

        save_airalo_credentials( $sandbox_client_id, $sandbox_client_secret, true );
    }
}

function save_airalo_settings(): void {
    $auto_publish = $_POST['airalo_auto_publish'] ?? 'off';
    $auto_publish_after_update = $_POST['airalo_auto_publish_update'] ?? 'off';
    $use_sandbox = $_POST['airalo_use_sandbox'] ?? 'off';
    $language = $_POST['airalo_language'] ?? 'en';
    $sync_images = $_POST['airalo_sync_images'] ?? 'off';

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
}

function save_airalo_credentials($clientId, $clientSecret, $isSandbox = false): void {
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

add_action( 'sync_products', 'sync_products_function', 10, 2 );

function sync_products_function() {
    $product_syncer = new \Airalo\Admin\Syncers\ProductSyncer();
    $product_syncer->handle();
}

add_filter( 'woocommerce_add_to_cart_validation', 'validate_cart_item_quantity', 10, 3 );

function validate_cart_item_quantity( $passed, $ignore_param, $quantity ) {
    return ( new OrderValidator )->handle( $passed, $quantity );
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

    ( new AiraloOrder )->handle( $order );
}

// TODO: this will most probably go away or will be refactored because we want on user history to add a link to a new page with instructions
add_action( 'woocommerce_order_details_after_order_table', 'display_custom_fields_on_user_history' );

function display_custom_fields_on_user_history( $order ) {
    ( new \Airalo\User\OrderDetails )->handle( $order );
}