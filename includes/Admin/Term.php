<?php

namespace Airalo\Admin;

use WP_Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Term {

    const IMAGE_NAME_PREFIX = 'operator_image_';
    const IMAGE_METADATA_KEY = 'image_id';

    public function fetch_or_create_image_term( $operator ) {
        $taxonomy_name = self::IMAGE_NAME_PREFIX . $operator->id;
        $term_name = $taxonomy_name . '_id';

        if ($term = get_term_by( 'slug', $term_name, $taxonomy_name )) {
            return $term;
        }

        // Taxonomies are stored in memory while terms are stored in db
        // every time sync runs we have to create 1 taxonomy per operator
        // to be able to fetch the term connected to it
        $this->create_image_taxonomy( $operator, $taxonomy_name, $term_name );

        return get_term_by( 'slug', $term_name, $taxonomy_name );
    }

    private function create_image_taxonomy( $operator, string $name, string $term_name ) {
        $labels = [
            'name' => _x( $name, 'taxonomy general name', 'textdomain' ),
            'singular_name' => _x( $name.'_singular', 'taxonomy singular name', 'textdomain' ),
        ];

        $args = [
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => [ 'slug' => $name ],
        ];

        $taxonomy = register_taxonomy( $name, ['post'], $args );

        $this->add_term_to_taxonomy( $taxonomy, $term_name, 'image_id', $operator );

        return $taxonomy;
    }

    private function add_term_to_taxonomy(WP_Taxonomy $taxonomy, $term_name, $term_metadata_key, $operator ) {
        if (! term_exists( $term_name, $taxonomy->name ) ) {
            $term = wp_insert_term( $term_name, $taxonomy->name, ['slug' => $term_name] );
            if  ( is_wp_error( $term ) ) {
                // error handling
                return;
            }


            $image_id = media_sideload_image( $operator->image->url, 0, null, 'id' );
            add_term_meta( $term['term_id'], $term_metadata_key, $image_id );
        }
    }
}