<?php

/**
 * Plugin Name: Airalo
 * Plugin URI: https://wordpress.org/plugins/airalo
 * Description: An ecommerce toolkit that helps you sell anything. Beautifully.
 * Version: 1.0.0
 * Author: Airalo
 * Author URI: https://airalo.com
 * Text Domain: airalo-plugin
 * Domain Path:
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * WC requires at least: 8.7
 * WC tested up to: 9.0.1
 *
 * License: GNU General Public License v2.0
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 */

defined( 'ABSPATH' ) || exit;

const AIRALO_PLUGIN_VERSION = '1.0.0';

require_once __DIR__ . '/vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/airalo_admin.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/airalo_schedule.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/instructions.php';

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'plugin_action_links' );

function plugin_action_links( $links ) {
    if ( ! is_plugin_active( 'airalo-woocommerce/airalo.php' ) ) {
        return $links;
    }

    $settings_link = ['settings' => '<a href="/wp-admin/admin.php?page=airalo-settings">Settings</a>'];

    return array_merge( $settings_link, $links );
}

add_action( 'admin_init', 'airalo_check_required_plugins' );
add_action( 'admin_notices', 'airalo_required_plugin_notice' );
register_activation_hook( __FILE__, 'airalo_check_required_plugins_on_activation' );

function airalo_check_required_plugins() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'airalo_required_plugin_notice' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

function airalo_required_plugin_notice() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        echo '<div class="notice notice-error"><p>';
        _e('Requires Woocommerce to be installed and active.', 'airalo-plugin');
        echo '</p></div>';
    }
}

function airalo_check_required_plugins_on_activation() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'activate_plugins' ) ) {
        wp_die( 'This plugin requires Woocommerce to be installed and active.' );
    }
}
