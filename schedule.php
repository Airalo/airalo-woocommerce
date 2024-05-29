<?php

use Airalo\Helpers\Cached;

require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function schedule_hourly_event() {
    if ( !wp_next_scheduled( 'hourly_cron_hook') ) {
        wp_schedule_event( time(), 'hourly', 'hourly_cron_hook' );
    }
}

add_action( 'wp', 'schedule_hourly_event' );

function schedule_daily_event() {
    if ( !wp_next_scheduled( 'daily_cron_hook') ) {
        wp_schedule_event( time(), 'daily', 'daily_cron_hook' );
    }
}

add_action( 'wp', 'schedule_daily_event' );

function sync_airalo_products_hourly() {

    ( new \Airalo\Admin\Syncers\ProductSyncer() )->handle();

}

add_action( 'hourly_cron_hook', 'sync_airalo_products_hourly' );


function clean_sdk_cache() {
    Cached::clearCache();
}

add_action( 'daily_cron_hook', 'clean_sdk_cache' );
