<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @return string
 */
function airalo_main() {
    $html_content = ( new \Airalo\User\MySimsPageBuilder() )->build_html();

    return wp_kses_post( $html_content );
}

add_shortcode( 'airalo_woocommerce_my_esim', 'airalo_main' );
