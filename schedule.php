<?php

require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function schedule_hourly_event() {
    if ( !wp_next_scheduled( 'airalo_hourly_cron_hook' ) ) {
        wp_schedule_event( time(), 'hourly', 'airalo_hourly_cron_hook' );
    }
}

add_action( 'wp', 'schedule_hourly_event' );

function sync_airalo_products_hourly() {

    ( new \Airalo\Admin\Syncers\ProductSyncer() )->handle();

}

add_action( 'airalo_hourly_cron_hook', 'sync_airalo_products_hourly' );
