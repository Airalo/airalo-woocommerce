<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @return string
 */
function airalo_main() {
    $allowed_tags = [
        'div' => [
            'class' => [],
            'id' => [],
        ],
        'p' => [
            'class' => [],
            'id' => [],
        ],
        'ul' => [
            'class' => [],
            'id' => [],
        ],
        'li' => [
            'class' => [],
            'id' => [],
        ],
        'a' => [
            'href' => [],
            'class' => [],
            'id' => [],
        ],
        'input' => [
            'type' => [],
            'name' => [],
            'id' => [],
            'class' => [],
            'checked' => [],
            'value' => [],
        ],
        'label' => [
            'for' => [],
            'class' => [],
            'id' => [],
        ],
        'select' => [
            'name' => [],
            'id' => [],
            'class' => [],
            'onchange' => []
        ],
        'option' => [
            'value' => [],
        ],
        'img' => [
            'src' => [],
            'width' => [],
            'height' => [],
            'alt' => [],
            'id' => [],
            'decoding' => [],
        ],
        'span' => [
            'class' => [],
            'style' => [],
            'id' => [],
        ],
        'strong' => [],
        'em' => [],
    ];
    $allowed_protocols = wp_allowed_protocols();
    $allowed_protocols[] = 'data';

    $html_content = ( new \Airalo\User\MySimsPageBuilder() )->build_html();

    return wp_kses( $html_content, $allowed_tags, $allowed_protocols );
}

add_shortcode( 'airalo_woocommerce_my_esim', 'airalo_main' );
