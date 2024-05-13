<?php

require_once __DIR__ . '/vendor/autoload.php';

add_filter('plugin_action_links_airalo', 'airalo_add_settings_link');

function airalo_add_settings_link($Links) {
    $settings_link = '<a href="admin.php?page=airalo-settings">Settings</a>';
    if (! $Links) {
        $links = [];
    }

    array_unshift($links, $settings_link);
    return $links;
}

add_action('admin_menu', 'airalo_menu');

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
    $auto_publish = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Option::AUTO_PUBLISH );
    $auto_publish_after_update = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Option::AUTO_PUBLISH_AFTER_UPDATE );
    $use_sandbox = $options->fetch_option_for_settings_page(\Airalo\Admin\Settings\Option::USE_SANDBOX);

    $last_sync = $options->fetch_option(\Airalo\Admin\Settings\Option::LAST_SYNC);
    $last_successful_sync = $options->fetch_option(\Airalo\Admin\Settings\Option::LAST_SUCCESSFUL_SYNC);

    $error = $options->fetch_option(\Airalo\Admin\Settings\Option::SYNC_ERROR);
    $show_error = $error ? 'visible': 'hidden';

    ?>

    <div>
        <h2> Airalo Plugin</h2>
        <form method="post" action="options.php" style="margin-left: 40px">
            <?php settings_fields('airalo-settings-group'); ?>
            <?php do_settings_sections('airalo-settings-group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Sync Products</th>
                    <td>
                        <input type="submit" name="sync_products" value="Sync Now" class="button button-primary"/>
                        <p><span style="font-weight: bold">Last Sync:</span> <?php echo $last_sync ?></p>
                        <p><span style="font-weight: bold">Last Successful Sync:</span> <?php echo $last_successful_sync?></p>
                        <p><span style="font-weight: bold; visibility: <?php echo $show_error ?>">Sync Error:</span> <?php echo $error?></p>
                    </td>
                </tr>
            </table>
            <br/>
            <table>
                <caption><h2>Credentials</h2></caption>
                <tr>
                    <th scope="row">Client Id</th>
                    <td>
                        <label>
                            <input type="text" name="airalo_client_id" value="<?php echo htmlspecialchars($client_id, ENT_QUOTES, 'UTF-8') ?>"/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Client Secret</th>
                    <td>
                        <label>
                            <input type="password" name="airalo_client_secret"/>
                        </label>
                    </td>
                </tr>
            </table>
            <input type="submit" name="save_airalo_credentials" value="Save Credentials" class="button button-primary" style="margin-top: 10px;margin-left: 125px;"/>
            <br>
            <br>
            <table>
                <caption><h2>Sandbox Credentials</h2></caption>
                <tr>
                    <th scope="row">Client Id</th>
                    <td>
                        <label>
                            <input type="text" name="airalo_client_id_sandbox" value="<?php echo htmlspecialchars($sandbox_client_id, ENT_QUOTES, 'UTF-8') ?>"/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Client Secret</th>
                    <td>
                        <label>
                            <input type="password" name="airalo_client_secret_sandbox"/>
                        </label>
                    </td>
                </tr>
            </table>
            <input type="submit" name="save_airalo_sandbox_credentials" value="Save Sandbox Credentials" class="button button-primary" style="margin-top: 10px;margin-left: 100px;"/>
            <br>
            <br>
            <table>
                <caption><h2>Settings</h2></caption>
                <tr>
                    <th scope="row">Use Sandbox</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airalo_use_sandbox" <?php echo $use_sandbox ?>/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Publish Product</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airalo_auto_publish" <?php echo $auto_publish ?>/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Publish After Price Update</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airalo_auto_publish_update" <?php echo $auto_publish_after_update ?>/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><input type="submit" name="save_airalo_settings" value="Save Settings" class="button button-primary" style="margin-top: 10px;margin-left: 50px;"/></td>
                </tr>
            </table>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'airalo_register_settings');

function airalo_register_settings () {
    register_setting('airalo-settings-group', 'airalo_settings');

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
        do_action('sync_products');
    }

    if ( isset( $_POST['save_airalo_credentials'] ) ) {
        $clientId = $_POST['airalo_client_id'] ?? null;
        $clientSecret = $_POST['airalo_client_secret'] ?? null;
        save_airalo_credentials(  $clientId, $clientSecret );
    }

    if ( isset ( $_POST['save_airalo_sandbox_credentials'] ) ) {
        $clientId = $_POST['airalo_client_id_sandbox'] ?? null;
        $clientSecret = $_POST['airalo_client_secret_sandbox'] ?? null;
        save_airalo_credentials( $clientId, $clientSecret, true );
    }

    if ( isset ( $_POST['save_airalo_settings'] ) ) {
        save_airalo_settings();
    }
}

function save_airalo_settings(): void {
    $auto_publish = $_POST['airalo_auto_publish'] ?? 'off';
    $auto_publish_after_update = $_POST['airalo_auto_publish_update'] ?? 'off';
    $use_sandbox = $_POST['airalo_use_sandbox'] ?? 'off';

    $options = new \Airalo\Admin\Settings\Option();

    $options->insert_option( \Airalo\Admin\Settings\Option::AUTO_PUBLISH, $auto_publish );
    $options->insert_option( \Airalo\Admin\Settings\Option::AUTO_PUBLISH_AFTER_UPDATE, $auto_publish_after_update );
    $options->insert_option( \Airalo\Admin\Settings\Option::USE_SANDBOX, $use_sandbox );
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
        $credentials->insert_credential($clientId, $clientIdCredential);
    }

    if ( $clientSecret ) {
        $credentials->insert_credential($clientId, $clientSecretCredential);
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
    // @TODO call api
    $json = '';

    $product_syncer = new \Airalo\Admin\Syncers\ProductSyncer( $json );
    $product_syncer->handle();
}

add_action('woocommerce_thankyou', 'identify_airalo_products', 10, 1);

function identify_airalo_products($order_id) {
    $order = wc_get_order($order_id);
    $items = $order->get_items();

    $order_item = new \Airalo\Admin\OrderItem( $items );
    $airalo_order_items = $order_item->get_airalo_order_items();
}

add_action('woocommerce_init', 'check_token_expiry');

function check_token_expiry() {
    $tokenHelper = new \Airalo\Admin\Helpers\TokenHelper();
    if ( $tokenHelper->is_token_expired() ) {
        $tokenHelper->renew_token();
    }
}