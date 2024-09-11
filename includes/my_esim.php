<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @return string
 */
function airalo_main() {
    return ( new \Airalo\User\MySimsPageBuilder() )->build_html();
}

add_shortcode( 'woocommerce_my_esim', 'airalo_main' );
