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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <style><?php require __DIR__ . '/airalo-css/pluginStyle.css' ?></style>
    

    <div id="airalo-container">
        <div class="airaloPluginHeader">
            <h2 class="airaloPluginTitle"> Airalo Plugin</h2>
            <div class="airaloLogoContainer">
                <?php require __DIR__ . '/airalo-images/logo-partner-platform.svg' ?>
            </div>
        </div>

        <div class="cardsContainer">
            <section>
                <div class="airaloCard">
                    <p class="cardTitle">General</p>

                    <div class="flexBox">
                        <p class="actionName">Sync Products</p>
                        <form method="post" action="options.php">
                            <?php settings_fields('airalo-settings-group'); ?>
                            <?php do_settings_sections('airalo-settings-group'); ?>
                            
                            <input type="submit" name="sync_products" value="Sync Now" class="airaloButton"/>
                        </form>
                    </div>
                </div>

                <div class="airaloCard settingsCard">
                    <p class="cardTitle">Settings</p>

                    <form method="post" action="options.php">
                        <?php settings_fields('airalo-settings-group'); ?>
                        <?php do_settings_sections('airalo-settings-group'); ?>
                        
                        <div>
                            <label for="airalo_use_sandbox">
                                Use Sandbox
                                <span class="switch">
                                <input type="checkbox" name="airalo_use_sandbox" <?php echo $useSandbox ?> id="airalo_use_sandbox"/>
                                <span class="slider round"></span>
                                </span>
                            </label>
                        </div>
                        
                        <div>
                            <label for="airalo_auto_publish">
                                Auto Publish Product
                                <span class="switch">
                                <input type="checkbox" name="airalo_auto_publish" <?php echo $autoPublish ?> id="airalo_auto_publish"/>
                                <span class="slider round"></span>
                                </span>
                            </label>
                        </div>
                        <div>
                            <label for="airalo_auto_publish_update">
                                Auto Publish After Price Update
                                <span class="switch">
                                <input type="checkbox" name="airalo_auto_publish_update" <?php echo $autoPublishAfterUpdate ?> id="airalo_auto_publish_update"/>
                                <span class="slider round"></span>
                                </span>
                            </label>
                        </div>

                        <div class="airaloButtonContainer">
                            <input type="submit" name="save_airalo_settings" value="Save" class="airaloButton"/>
                        </div>
                    </form>
                    
                </div>

            </section>

            <section>
                <div class="airaloCard credentialsCard">
                    <p class="cardTitle">Credentials</p>

                    <form method="post" action="options.php">
                        <?php settings_fields('airalo-settings-group'); ?>
                        <?php do_settings_sections('airalo-settings-group'); ?>
                        <div>
                            <label for="airalo_client_id_sandbox">Client Id</label>
                            <input type="text" name="airalo_client_id" value="<?php echo htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?>"/>
                        </div>


                        <div>
                            <label for="airalo_client_secret_sandbox">Client Secret</label>
                            <input type="password" name="airalo_client_secret" placeholder="Enter Secret"/>
                        </div>


                        <div class="airaloButtonContainer">
                            <input type="submit" name="save_airalo_credentials" value="Save Credentials" class="airaloButton"/>
                        </div>
                    </form>
                </div>

                <div class="airaloCard credentialsCard">
                    <p class="cardTitle">Sandbox Credentials</p>


                    <form method="post" action="options.php">
                        <?php settings_fields('airalo-settings-group'); ?>
                        <?php do_settings_sections('airalo-settings-group'); ?>
                        
                        <div>
                            <label for="airalo_client_id_sandbox">Client Id</label>
                            <input type="text" name="airalo_client_id_sandbox" placeholder="Enter ID" value="<?php echo htmlspecialchars($sandboxClientId, ENT_QUOTES, 'UTF-8') ?>"/>
                        </div>
                        
                        <div>
                            <label for="airalo_client_secret_sandbox">Client Secret</label>
                            <input type="password" name="airalo_client_secret_sandbox" placeholder="Enter Secret"/>
                        </div>

                        <div class="airaloButtonContainer">
                            <input type="submit" name="save_airalo_sandbox_credentials" value="Save Sandbox Credentials" class="airaloButton"/>
                        </div>
                    </form>


                </div>
            </section>
        </div>
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

add_action('woocommerce_thankyou', 'identify_airalo_products', 10, 1);

function identify_airalo_products($order_id) {
    $order = wc_get_order($order_id);
    $items = $order->get_items();

    $orderItem = new \Airalo\Admin\OrderItem($items);
    $airaloOrderItems = $orderItem->getAiraloOrderItems();
}

add_action('woocommerce_init', 'check_token_expiry');

function check_token_expiry() {
    $tokenHelper = new \Airalo\Admin\Helpers\TokenHelper();
    if ( $tokenHelper->isTokenExpired() ) {
        $tokenHelper->renewToken();
    }
}