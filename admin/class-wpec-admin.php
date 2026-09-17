<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
        add_filter( 'manage_wpec_event_posts_columns',       [ __CLASS__, 'event_columns' ] );
        add_action( 'manage_wpec_event_posts_custom_column', [ __CLASS__, 'event_column_data' ], 10, 2 );
        add_filter( 'manage_edit-wpec_event_sortable_columns', [ __CLASS__, 'sortable_columns' ] );
        add_action( 'pre_get_posts',         [ __CLASS__, 'sort_by_event_date' ] );
        add_filter( 'post_row_actions',      [ __CLASS__, 'row_actions' ], 10, 2 );
    }

    // ------------------------------------------------------------------
    // Menus
    // ------------------------------------------------------------------
    public static function register_menus(): void {
        add_menu_page(
            __( 'Events Calendar', 'wp-events-calendar' ),
            __( 'Events Calendar', 'wp-events-calendar' ),
            'edit_posts',
            'wpec-events',
            [ __CLASS__, 'page_events' ],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page( 'wpec-events', __( 'All Events', 'wp-events-calendar' ),       __( 'All Events', 'wp-events-calendar' ),       'edit_posts',    'wpec-events',       [ __CLASS__, 'page_events' ] );
        add_submenu_page( 'wpec-events', __( 'Add New Event', 'wp-events-calendar' ),    __( 'Add New Event', 'wp-events-calendar' ),    'edit_posts',    'post-new.php?post_type=wpec_event', null );
        add_submenu_page( 'wpec-events', __( 'Organizers', 'wp-events-calendar' ),       __( 'Organizers', 'wp-events-calendar' ),       'edit_posts',    'wpec-organizers',   [ __CLASS__, 'page_organizers' ] );
        add_submenu_page( 'wpec-events', __( 'Bookings', 'wp-events-calendar' ),         __( 'Bookings', 'wp-events-calendar' ),         'manage_options','wpec-bookings',     [ __CLASS__, 'page_bookings' ] );
        add_submenu_page( 'wpec-events', __( 'Import / Export', 'wp-events-calendar' ),  __( 'Import / Export', 'wp-events-calendar' ),  'manage_options','wpec-import-export',[ __CLASS__, 'page_import_export' ] );
        add_submenu_page( 'wpec-events', __( 'Settings', 'wp-events-calendar' ),         __( 'Settings', 'wp-events-calendar' ),         'manage_options','wpec-settings',     [ __CLASS__, 'page_settings' ] );
        add_submenu_page( 'wpec-events', __( 'Shortcode Generator', 'wp-events-calendar' ), __( 'Shortcode', 'wp-events-calendar' ),     'edit_posts',    'wpec-shortcode',    [ __CLASS__, 'page_shortcode' ] );
    }

    // ------------------------------------------------------------------
    // Scripts & styles
    // ------------------------------------------------------------------
    public static function enqueue_scripts( string $hook ): void {
        $wpec_pages = [
            'toplevel_page_wpec-events', 'events-calendar_page_wpec-organizers',
            'events-calendar_page_wpec-bookings', 'events-calendar_page_wpec-import-export',
            'events-calendar_page_wpec-settings', 'events-calendar_page_wpec-shortcode',
        ];

        if ( ! in_array( $hook, $wpec_pages, true ) && ! in_array( get_current_screen()?->post_type, [ 'wpec_event' ], true ) ) return;

        wp_enqueue_style( 'wpec-admin', WPEC_PLUGIN_URL . 'admin/css/admin.css', [], WPEC_VERSION );
        wp_enqueue_script( 'wpec-admin', WPEC_PLUGIN_URL . 'admin/js/admin.js', [ 'jquery' ], WPEC_VERSION, true );
        wp_localize_script( 'wpec-admin', 'wpecAdmin', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'wpec_admin_nonce' ),
            'metaNonce' => wp_create_nonce( 'wpec_meta_nonce' ),
            'i18n'      => [
                'confirmDelete'  => __( 'Are you sure you want to delete this?', 'wp-events-calendar' ),
                'cacheCleared'   => __( 'Cache cleared successfully!', 'wp-events-calendar' ),
                'saving'         => __( 'Saving…', 'wp-events-calendar' ),
                'saved'          => __( 'Saved!', 'wp-events-calendar' ),
                'error'          => __( 'An error occurred. Please try again.', 'wp-events-calendar' ),
                'importing'      => __( 'Importing…', 'wp-events-calendar' ),
                'exporting'      => __( 'Exporting…', 'wp-events-calendar' ),
            ],
        ] );
    }

    // ------------------------------------------------------------------
    // All Events page (list table wrapper)
    // ------------------------------------------------------------------
    // FIX: wp_redirect() after output causes "headers already sent".
    // Redirect must happen before any HTML is output.
    public static function page_events(): void {
        wp_redirect( admin_url( 'edit.php?post_type=wpec_event' ) );
        exit;
    }

    // ------------------------------------------------------------------
    // Organizers page
    // ------------------------------------------------------------------
    public static function page_organizers(): void {
        global $wpdb;

        // Handle form submissions
        if ( isset( $_POST['wpec_organizer_action'], $_POST['_wpnonce'] ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'wp-events-calendar' ) );
            }
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wpec_organizer_save' ) ) {
                wp_die( 'Security check failed' );
            }

            $action = sanitize_key( $_POST['wpec_organizer_action'] );

            if ( $action === 'save' ) {
                $id      = absint( $_POST['org_id'] ?? 0 );
                $name    = sanitize_text_field( wp_unslash( $_POST['org_name'] ?? '' ) );
                $email   = sanitize_email( wp_unslash( $_POST['org_email'] ?? '' ) );
                $phone   = sanitize_text_field( wp_unslash( $_POST['org_phone'] ?? '' ) );
                $website = esc_url_raw( wp_unslash( $_POST['org_website'] ?? '' ) );
                $desc    = sanitize_textarea_field( wp_unslash( $_POST['org_description'] ?? '' ) );

                if ( $id ) {
                    $wpdb->update( $wpdb->prefix . 'wpec_organizers',
                        compact( 'name', 'email', 'phone', 'website', 'desc' ),
                        [ 'id' => $id ], [ '%s','%s','%s','%s','%s' ], [ '%d' ]
                    );
                } else {
                    $wpdb->insert( $wpdb->prefix . 'wpec_organizers',
                        compact( 'name', 'email', 'phone', 'website', 'desc' ),
                        [ '%s','%s','%s','%s','%s' ]
                    );
                }
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Organizer saved.', 'wp-events-calendar' ) . '</p></div>';

            } elseif ( $action === 'delete' ) {
                $id = absint( $_POST['org_id'] ?? 0 );
                $wpdb->delete( $wpdb->prefix . 'wpec_organizers', [ 'id' => $id ], [ '%d' ] );
                $wpdb->delete( $wpdb->prefix . 'wpec_event_organizers', [ 'organizer_id' => $id ], [ '%d' ] );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Organizer deleted.', 'wp-events-calendar' ) . '</p></div>';
            }
        }

        $editing    = null;
        $edit_id    = absint( $_GET['edit'] ?? 0 );
        if ( $edit_id ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpec_organizers WHERE id = %d", $edit_id ) );
        }

        $organizers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpec_organizers ORDER BY name ASC" );
        include WPEC_PLUGIN_DIR . 'admin/views/organizers.php';
    }

    // ------------------------------------------------------------------
    // Bookings page
    // ------------------------------------------------------------------
    public static function page_bookings(): void {
        include WPEC_PLUGIN_DIR . 'admin/views/bookings.php';
    }

    // ------------------------------------------------------------------
    // Import / Export page
    // ------------------------------------------------------------------
    public static function page_import_export(): void {
        include WPEC_PLUGIN_DIR . 'admin/views/import-export.php';
    }

    // ------------------------------------------------------------------
    // Settings page
    // ------------------------------------------------------------------
    public static function page_settings(): void {
        if ( isset( $_POST['wpec_save_settings'], $_POST['_wpnonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wpec_settings_save' ) ) {
                wp_die( 'Security check failed' );
            }
            self::save_settings();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wp-events-calendar' ) . '</p></div>';
        }
        $tab      = sanitize_key( $_GET['tab'] ?? 'general' );
        $settings = WPEC_Settings::get_all();
        include WPEC_PLUGIN_DIR . 'admin/views/settings.php';
    }

    private static function save_settings(): void {
        $fields = [
            'wpec_event_slug'             => 'sanitize_title',
            'wpec_events_page_id'         => 'absint',
            'wpec_calendar_template'      => 'sanitize_text_field',
            'wpec_default_view'           => 'sanitize_text_field',
            'wpec_events_per_page'        => 'absint',
            'wpec_date_format'            => 'sanitize_text_field',
            'wpec_time_format'            => 'sanitize_text_field',
            'wpec_timezone'               => 'sanitize_text_field',
            'wpec_week_starts_on'         => 'absint',
            'wpec_currency'               => 'sanitize_text_field',
            'wpec_currency_position'      => 'sanitize_text_field',
            'wpec_default_map_provider'   => 'sanitize_text_field',
            'wpec_google_maps_api_key'    => 'sanitize_text_field',
            'wpec_google_calendar_id'     => 'sanitize_text_field',
            'wpec_google_client_id'       => 'sanitize_text_field',
            'wpec_google_client_secret'   => 'sanitize_text_field',
            'wpec_paypal_email'           => 'sanitize_email',
            'wpec_stripe_public_key'      => 'sanitize_text_field',
            'wpec_stripe_secret_key'      => 'sanitize_text_field',
            'wpec_theme_primary_color'    => 'sanitize_hex_color',
            'wpec_theme_secondary_color'  => 'sanitize_hex_color',
            'wpec_theme_text_color'       => 'sanitize_hex_color',
            'wpec_theme_bg_color'         => 'sanitize_hex_color',
            'wpec_additional_content'     => 'wp_kses_post',
        ];

        foreach ( $fields as $key => $sanitizer ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_option( $key, $sanitizer( wp_unslash( $_POST[ $key ] ) ) );
            }
        }

        // Only update checkboxes for the active tab to prevent erasing options from other tabs
        $current_tab = sanitize_key( $_POST['wpec_current_tab'] ?? '' );
        $tab_checkboxes = [
            'general' => [ 'wpec_show_past_events', 'wpec_enable_comments' ],
            'display' => [ 'wpec_show_view_switcher', 'wpec_show_cat_filter', 'wpec_show_venue', 'wpec_show_cost', 'wpec_show_add_to_calendar' ],
            'maps'    => [ 'wpec_show_map_by_default' ],
            'booking' => [ 'wpec_booking_enabled', 'wpec_payment_enabled', 'wpec_paypal_enabled', 'wpec_paypal_sandbox', 'wpec_stripe_enabled' ],
        ];

        if ( isset( $tab_checkboxes[ $current_tab ] ) ) {
            foreach ( $tab_checkboxes[ $current_tab ] as $key ) {
                update_option( $key, isset( $_POST[ $key ] ) ? true : false );
            }
        }

        WPEC_Cache::flush_calendar_caches();
    }

    // ------------------------------------------------------------------
    // Shortcode generator page
    // ------------------------------------------------------------------
    public static function page_shortcode(): void {
        include WPEC_PLUGIN_DIR . 'admin/views/shortcode-generator.php';
    }

    // ------------------------------------------------------------------
    // Admin columns
    // ------------------------------------------------------------------
    public static function event_columns( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['wpec_start_date'] = __( 'Start Date', 'wp-events-calendar' );
                $new['wpec_end_date']   = __( 'End Date', 'wp-events-calendar' );
                $new['wpec_venue']      = __( 'Venue', 'wp-events-calendar' );
                $new['wpec_cost']       = __( 'Cost', 'wp-events-calendar' );
            }
        }
        return $new;
    }

    public static function event_column_data( string $column, int $post_id ): void {
        $meta = WPEC_Helpers::get_event_meta( $post_id );
        switch ( $column ) {
            case 'wpec_start_date':
                echo esc_html( $meta['start_formatted'] ?: '—' );
                break;
            case 'wpec_end_date':
                echo esc_html( $meta['end_formatted'] ?: '—' );
                break;
            case 'wpec_venue':
                echo esc_html( $meta['venue_name'] ?: ( $meta['city'] ?: '—' ) );
                break;
            case 'wpec_cost':
                echo $meta['cost']
                    ? esc_html( WPEC_Helpers::format_price( (float) $meta['cost'], $meta['currency'] ) )
                    : '<span class="wpec-free-badge">' . esc_html__( 'Free', 'wp-events-calendar' ) . '</span>';
                break;
        }
    }

    public static function sortable_columns( array $columns ): array {
        $columns['wpec_start_date'] = 'wpec_start_date';
        return $columns;
    }

    public static function sort_by_event_date( \WP_Query $query ): void {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( $query->get( 'post_type' ) !== 'wpec_event' ) return;
        if ( $query->get( 'orderby' ) === 'wpec_start_date' ) {
            $query->set( 'meta_key', '_wpec_start_date' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    // FIX BUG #2: missing handler for duplicate action
    public static function duplicate_event(): void {
        if ( ! isset( $_GET['post'], $_GET['_wpnonce'] ) ) {
            wp_die( 'Missing parameters.' );
        }
        $post_id = absint( $_GET['post'] );
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpec_duplicate_' . $post_id ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( 'Permission denied.' );
        }
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'wpec_event' ) {
            wp_die( 'Event not found.' );
        }

        $new_id = wp_insert_post( [
            'post_title'   => $post->post_title . ' ' . __( '(Copy)', 'wp-events-calendar' ),
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => 'wpec_event',
            'post_author'  => get_current_user_id(),
        ] );

        if ( is_wp_error( $new_id ) ) {
            wp_die( esc_html( $new_id->get_error_message() ) );
        }

        // Copy all meta
        $metas = get_post_meta( $post_id );
        foreach ( $metas as $key => $values ) {
            foreach ( $values as $value ) {
                add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
            }
        }

        // Copy thumbnail
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            set_post_thumbnail( $new_id, $thumb_id );
        }

        // Copy taxonomies
        foreach ( [ 'wpec_event_cat', 'wpec_event_tag' ] as $tax ) {
            $terms = wp_get_object_terms( $post_id, $tax, [ 'fields' => 'ids' ] );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $new_id, $terms, $tax );
            }
        }

        // Clear cached event ID from Google Calendar on the copy
        delete_post_meta( $new_id, '_wpec_gcal_event_id' );

        WPEC_Cache::flush_calendar_caches();
        wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
        exit;
    }

    public static function row_actions( array $actions, \WP_Post $post ): array {
        if ( $post->post_type === 'wpec_event' ) {
            $actions['wpec_duplicate'] = '<a href="' . esc_url( wp_nonce_url(
                admin_url( 'admin.php?action=wpec_duplicate_event&post=' . $post->ID ),
                'wpec_duplicate_' . $post->ID
            ) ) . '">' . esc_html__( 'Duplicate', 'wp-events-calendar' ) . '</a>';
        }
        return $actions;
    }
}
