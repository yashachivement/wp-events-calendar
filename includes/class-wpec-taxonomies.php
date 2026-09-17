<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Taxonomies {

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
    }

    public static function register_taxonomies(): void {
        // Event Category
        register_taxonomy( 'wpec_event_cat', 'wpec_event', [
            'hierarchical'      => true,
            'labels'            => [
                'name'              => _x( 'Event Categories', 'taxonomy general name', 'wp-events-calendar' ),
                'singular_name'     => _x( 'Event Category', 'taxonomy singular name', 'wp-events-calendar' ),
                'search_items'      => __( 'Search Event Categories', 'wp-events-calendar' ),
                'all_items'         => __( 'All Event Categories', 'wp-events-calendar' ),
                'parent_item'       => __( 'Parent Event Category', 'wp-events-calendar' ),
                'parent_item_colon' => __( 'Parent Event Category:', 'wp-events-calendar' ),
                'edit_item'         => __( 'Edit Event Category', 'wp-events-calendar' ),
                'update_item'       => __( 'Update Event Category', 'wp-events-calendar' ),
                'add_new_item'      => __( 'Add New Event Category', 'wp-events-calendar' ),
                'new_item_name'     => __( 'New Event Category Name', 'wp-events-calendar' ),
                'menu_name'         => __( 'Categories', 'wp-events-calendar' ),
            ],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'event-category' ],
            'show_in_rest'      => true,
        ] );

        // Event Tag
        register_taxonomy( 'wpec_event_tag', 'wpec_event', [
            'hierarchical'      => false,
            'labels'            => [
                'name'                       => _x( 'Event Tags', 'taxonomy general name', 'wp-events-calendar' ),
                'singular_name'              => _x( 'Event Tag', 'taxonomy singular name', 'wp-events-calendar' ),
                'search_items'               => __( 'Search Event Tags', 'wp-events-calendar' ),
                'popular_items'              => __( 'Popular Event Tags', 'wp-events-calendar' ),
                'all_items'                  => __( 'All Event Tags', 'wp-events-calendar' ),
                'edit_item'                  => __( 'Edit Event Tag', 'wp-events-calendar' ),
                'update_item'                => __( 'Update Event Tag', 'wp-events-calendar' ),
                'add_new_item'               => __( 'Add New Event Tag', 'wp-events-calendar' ),
                'new_item_name'              => __( 'New Event Tag Name', 'wp-events-calendar' ),
                'separate_items_with_commas' => __( 'Separate event tags with commas', 'wp-events-calendar' ),
                'add_or_remove_items'        => __( 'Add or remove event tags', 'wp-events-calendar' ),
                'choose_from_most_used'      => __( 'Choose from the most used event tags', 'wp-events-calendar' ),
                'menu_name'                  => __( 'Tags', 'wp-events-calendar' ),
            ],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'event-tag' ],
            'show_in_rest'      => true,
        ] );
    }
}
