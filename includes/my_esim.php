<?php

/**
 * @return string
 */
function main() {
    return ( new \Airalo\User\MySimsPageBuilder() )->build_html();
}

add_shortcode( 'woocommerce_my_esim', 'main' );
