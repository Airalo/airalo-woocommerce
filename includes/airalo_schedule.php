<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Airalo\Helpers\Cached;

require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';

function airalo_schedule_hourly_event() {
	if ( !wp_next_scheduled( 'airalo_hourly_cron_hook' ) ) {
		wp_schedule_event( time(), 'hourly', 'airalo_hourly_cron_hook' );
	}
}

add_action( 'wp', 'airalo_schedule_hourly_event' );

function airalo_schedule_daily_event() {
	if ( !wp_next_scheduled( 'airalo_daily_cron_hook') ) {
		wp_schedule_event( time(), 'daily', 'airalo_daily_cron_hook' );
	}
}

add_action( 'wp', 'airalo_schedule_daily_event' );

function airalo_sync_products_hourly() {

	( new \Airalo\Admin\Syncers\ProductSyncer() )->handle();

}

add_action( 'airalo_hourly_cron_hook', 'airalo_sync_products_hourly' );

function airalo_clean_sdk_cache() {
	Cached::clearCache();
}

add_action( 'airalo_daily_cron_hook', 'airalo_clean_sdk_cache' );
