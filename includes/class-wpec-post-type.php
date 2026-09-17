<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Post_Type {

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_filter( 'post_updated_messages', [ __CLASS__, 'updated_messages' ] );
        add_filter( 'bulk_post_updated_messages', [ __CLASS__, 'bulk_updated_messages' ], 10, 2 );
    }

    public static function register_post_type(): void {
        $labels = [
            'name'                  => _x( 'Events', 'post type general name', 'wp-events-calendar' ),
            'singular_name'         => _x( 'Event', 'post type singular name', 'wp-events-calendar' ),
            'menu_name'             => _x( 'Events Calendar', 'admin menu', 'wp-events-calendar' ),
            'name_admin_bar'        => _x( 'Event', 'add new on admin bar', 'wp-events-calendar' ),
            'add_new'               => __( 'Add New', 'wp-events-calendar' ),
            'add_new_item'          => __( 'Add New Event', 'wp-events-calendar' ),
            'new_item'              => __( 'New Event', 'wp-events-calendar' ),
            'edit_item'             => __( 'Edit Event', 'wp-events-calendar' ),
            'view_item'             => __( 'View Event', 'wp-events-calendar' ),
            'all_items'             => __( 'All Events', 'wp-events-calendar' ),
            'search_items'          => __( 'Search Events', 'wp-events-calendar' ),
            'parent_item_colon'     => __( 'Parent Events:', 'wp-events-calendar' ),
            'not_found'             => __( 'No events found.', 'wp-events-calendar' ),
            'not_found_in_trash'    => __( 'No events found in Trash.', 'wp-events-calendar' ),
            'featured_image'        => __( 'Event Image', 'wp-events-calendar' ),
            'set_featured_image'    => __( 'Set event image', 'wp-events-calendar' ),
            'remove_featured_image' => __( 'Remove event image', 'wp-events-calendar' ),
            'use_featured_image'    => __( 'Use as event image', 'wp-events-calendar' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,  // We place it under our own menu
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'event', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => 'events',
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-calendar-alt',
        ];

        register_post_type( 'wpec_event', $args );
    }

    public static function updated_messages( array $messages ): array {
        global $post;
        $messages['wpec_event'] = [
            0  => '',
            1  => __( 'Event updated.', 'wp-events-calendar' ),
            2  => __( 'Custom field updated.', 'wp-events-calendar' ),
            3  => __( 'Custom field deleted.', 'wp-events-calendar' ),
            4  => __( 'Event updated.', 'wp-events-calendar' ),
            5  => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s', 'wp-events-calendar' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6  => __( 'Event published.', 'wp-events-calendar' ),
            7  => __( 'Event saved.', 'wp-events-calendar' ),
            8  => __( 'Event submitted.', 'wp-events-calendar' ),
            9  => sprintf( __( 'Event scheduled for: <strong>%1$s</strong>.', 'wp-events-calendar' ), date_i18n( __( 'M j, Y @ G:i', 'wp-events-calendar' ), strtotime( $post->post_date ) ) ),
            10 => __( 'Event draft updated.', 'wp-events-calendar' ),
        ];
        return $messages;
    }

    public static function bulk_updated_messages( array $bulk_messages, array $bulk_counts ): array {
        $bulk_messages['wpec_event'] = [
            'updated'   => _n( '%s event updated.', '%s events updated.', $bulk_counts['updated'], 'wp-events-calendar' ),
            'locked'    => _n( '%s event not updated, somebody is editing it.', '%s events not updated, somebody is editing them.', $bulk_counts['locked'], 'wp-events-calendar' ),
            'deleted'   => _n( '%s event permanently deleted.', '%s events permanently deleted.', $bulk_counts['deleted'], 'wp-events-calendar' ),
            'trashed'   => _n( '%s event moved to the Trash.', '%s events moved to the Trash.', $bulk_counts['trashed'], 'wp-events-calendar' ),
            'untrashed' => _n( '%s event restored from the Trash.', '%s events restored from the Trash.', $bulk_counts['untrashed'], 'wp-events-calendar' ),
        ];
        return $bulk_messages;
    }
}
