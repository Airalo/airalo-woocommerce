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
}

function airalo_main_section_db() {
    echo '<p>Settings</p>';
}

function airalo_settings_field_cb() {
    $options = get_options('airalo-options');
    echo '<p>yeet</p>';
}

add_action('sync_products', 'sync_products_function', 10, 2);

function sync_products_function() {
    // @TODO call api
    $json = '';

    $productSyncer = new \Airalo\Admin\Syncers\ProductSyncer($json);
    $productSyncer->handle();
}