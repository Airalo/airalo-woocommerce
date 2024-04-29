<?php

namespace Airalo\Admin;

use WP_Taxonomy;

class Term {

    private const IMAGE_NAME_PREFIX = 'operator_image_';
    const IMAGE_METADATA_KEY = 'image_id';

    public function fetch_or_create_image_term( array $operator ) {
        $taxonomyName = self::IMAGE_NAME_PREFIX . $operator['id'];
        $termName = $taxonomyName . '_id';

        if ($term = get_term_by( 'slug', $termName, $taxonomyName )) {
            return $term;
        }

        // Taxonomies are stored in memory while terms are stored in db
        // every time sync runs we have to create 1 taxonomy per operator
        // to be able to fetch the term connected to it
        $this->create_image_taxonomy( $operator, $taxonomyName, $termName );

        return get_term_by( 'slug', $termName, $taxonomyName );
    }

    private function create_image_taxonomy( array $operator, string $name, string $termName ) {
        $labels = [
            'name' => _x($name, 'taxonomy general name', 'textdomain'),
            'singular_name' => _x($name.'_singular', 'taxonomy singular name', 'textdomain'),
        ];

        $args = [
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => $name],
        ];

        $taxonomy = register_taxonomy( $name, ['post'], $args );

        $this->add_term_to_taxonomy( $taxonomy, $termName, 'image_id', $operator );

        return $taxonomy;
    }

    private function add_term_to_taxonomy( WP_Taxonomy $taxonomy, $termName, $termMetadataKey, $operator ) {
        if (! term_exists( $termName, $taxonomy->name ) ) {
            $term = wp_insert_term( $termName, $taxonomy->name, ['slug' => $termName] );
            if  ( is_wp_error( $term ) ) {
                // error handling
                return;
            }

            $imageId = media_sideload_image( $operator['image']['url'], 0, null, 'id' );
            add_term_meta( $term['term_id'], $termMetadataKey, $imageId );
        }
    }

    private function delete_all_terms($taxonomyName, $termName) {
        $term = get_term_by('slug', $termName, $taxonomyName);
        if (is_wp_error($term)) {
            return;
        }

        wp_delete_term($term->term_id, $taxonomyName);
    }
}