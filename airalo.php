<?php

/**
 * Plugin Name: Airalo
 * Plugin URI: https://airalo.com/
 * Description: An ecommerce toolkit that helps you sell anything. Beautifully.
 * Version: 1.0.0
 * Author: Airalo
 * Author URI: https://airalo.com
 * Text Domain: test
 * Domain Path: /i18n/languages/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'admin.php';
require_once plugin_dir_path( __FILE__ ) . 'schedule.php';

