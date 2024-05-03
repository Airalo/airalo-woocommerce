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
    $credentials = new \Airalo\Admin\Settings\Credentials();
    $clientId = $credentials->get_credential( \Airalo\Admin\Settings\Credentials::CLIENT_ID );
    $sandboxClientId = $credentials->get_credential( \Airalo\Admin\Settings\Credentials::CLIENT_ID_SANDBOX );

    $options = new \Airalo\Admin\Settings\Options();
    $autoPublish = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Options::AUTO_PUBLISH );
    $autoPublishAfterUpdate = $options->fetch_option_for_settings_page( \Airalo\Admin\Settings\Options::AUTO_PUBLISH_AFTER_UPDATE );
    $useSandbox = $options->fetch_option_for_settings_page(\Airalo\Admin\Settings\Options::USE_SANDBOX);

    ?>

    <div>
        <h2> Airalo Plugin</h2>
        <form method="post" action="options.php">
            <?php settings_fields('airalo-settings-group'); ?>
            <?php do_settings_sections('airalo-settings-group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Sync Products</th>
                    <td><input type="submit" name="sync_products" value="Sync Now" class="button button-primary"/></td>
                </tr>
            </table>
            <br/>
            <table>
                <caption><h2>Credentials</h2></caption>
                <tr>
                    <th scope="row">Client Id</th>
                    <td>
                        <label>
                            <input type="text" name="airalo_client_id" value="<?php echo htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?>"/>
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
                <tr>
                    <td><input type="submit" name="save_airalo_credentials" value="Save Credentials" class="button button-primary"/></td>
                </tr>
            </table>
            <br>
            <table>
                <caption><h2>Sandbox Credentials</h2></caption>
                <tr>
                    <th scope="row">Client Id</th>
                    <td>
                        <label>
                            <input type="text" name="airalo_client_id_sandbox" value="<?php echo htmlspecialchars($sandboxClientId, ENT_QUOTES, 'UTF-8') ?>"/>
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
                <tr>
                    <td><input type="submit" name="save_airalo_sandbox_credentials" value="Save Sandbox Credentials" class="button button-primary"/></td>
                </tr>
            </table>
            <br>
            <table>
                <caption><h2>Settings</h2></caption>
                <tr>
                    <th scope="row">Use Sandbox</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airalo_use_sandbox" <?php echo $useSandbox ?>/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Publish Product</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airalo_auto_publish" <?php echo $autoPublish ?>/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Publish After Price Update</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airalo_auto_publish_update" <?php echo $autoPublishAfterUpdate ?>/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><input type="submit" name="save_airalo_settings" value="Save Settings" class="button button-primary"/></td>
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
    $autoPublish = $_POST['airalo_auto_publish'] ?? 'off';
    $autoPublishAfterUpdate = $_POST['airalo_auto_publish_update'] ?? 'off';
    $useSandbox = $_POST['airalo_use_sandbox'] ?? 'off';

    $options = new \Airalo\Admin\Settings\Options();

    $options->insert_option( \Airalo\Admin\Settings\Options::AUTO_PUBLISH, $autoPublish );
    $options->insert_option( \Airalo\Admin\Settings\Options::AUTO_PUBLISH_AFTER_UPDATE, $autoPublishAfterUpdate );
    $options->insert_option( \Airalo\Admin\Settings\Options::USE_SANDBOX, $useSandbox );
}

function save_airalo_credentials($clientId, $clientSecret, $isSandbox = false): void {
    $clientIdCredential = \Airalo\Admin\Settings\Credentials::CLIENT_ID;
    $clientSecretCredential = \Airalo\Admin\Settings\Credentials::CLIENT_SECRET;

    if ( $isSandbox ) {
        $clientIdCredential = \Airalo\Admin\Settings\Credentials::CLIENT_ID_SANDBOX;
        $clientSecretCredential = \Airalo\Admin\Settings\Credentials::CLIENT_SECRET_SANDBOX;
    }

    $credentials = new \Airalo\Admin\Settings\Credentials();

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
    $options = get_options('airalo-options');
    echo '<p>Settings</p>';
}

add_action('sync_products', 'sync_products_function', 10, 2);

function sync_products_function() {
    // @TODO call api
    $json = '';

    $productSyncer = new \Airalo\Admin\Syncers\ProductSyncer($json);
    $productSyncer->handle();
}