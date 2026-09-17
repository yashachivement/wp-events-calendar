<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Shortcode {

    public static function init(): void {
        add_shortcode( 'wpec_calendar', [ __CLASS__, 'render' ] );
        add_shortcode( 'wpec_events_list', [ __CLASS__, 'render_list' ] );
        add_action( 'wp_ajax_wpec_get_events',         [ __CLASS__, 'ajax_get_events' ] );
        add_action( 'wp_ajax_nopriv_wpec_get_events',  [ __CLASS__, 'ajax_get_events' ] );
    }

    // ------------------------------------------------------------------
    // [wpec_calendar] shortcode
    // ------------------------------------------------------------------
    public static function render( array $atts ): string {
        $settings = WPEC_Settings::get_all();

        $atts = shortcode_atts( [
            'view'       => $settings['default_view'],
            'template'   => $settings['calendar_template'],
            'category'   => '',
            'per_page'   => $settings['events_per_page'],
        ], $atts, 'wpec_calendar' );

        ob_start();
        $template_file = WPEC_PLUGIN_DIR . 'public/templates/' . sanitize_file_name( $atts['template'] ) . '/calendar.php';
        if ( ! file_exists( $template_file ) ) {
            $template_file = WPEC_PLUGIN_DIR . 'public/templates/classic/calendar.php';
        }

        $view     = sanitize_key( $atts['view'] );
        $category = sanitize_text_field( $atts['category'] );
        $per_page = absint( $atts['per_page'] );

        include $template_file;
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // [wpec_events_list] simple list shortcode
    // ------------------------------------------------------------------
    public static function render_list( array $atts ): string {
        $atts = shortcode_atts( [
            'limit'    => 5,
            'category' => '',
            'upcoming' => 'true',
        ], $atts, 'wpec_events_list' );

        $query_args = [
            'post_type'      => 'wpec_event',
            'post_status'    => 'publish',
            'posts_per_page' => absint( $atts['limit'] ),
            'meta_key'       => '_wpec_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ];

        if ( $atts['upcoming'] === 'true' ) {
            $query_args['meta_query'] = [ [
                'key'     => '_wpec_start_date',
                'value'   => date( 'Y-m-d' ),
                'compare' => '>=',
                'type'    => 'DATE',
            ] ];
        }

        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'wpec_event_cat',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ] ];
        }

        $events = get_posts( $query_args );

        if ( empty( $events ) ) {
            return '<p class="wpec-no-events">' . esc_html__( 'No upcoming events.', 'wp-events-calendar' ) . '</p>';
        }

        ob_start();
        echo '<ul class="wpec-events-list">';
        foreach ( $events as $event ) {
            $meta = WPEC_Helpers::get_event_meta( $event->ID );
            echo '<li class="wpec-list-item">';
            echo '<a href="' . esc_url( get_permalink( $event->ID ) ) . '" class="wpec-list-title">' . esc_html( $event->post_title ) . '</a>';
            echo '<span class="wpec-list-date">' . esc_html( $meta['start_formatted'] ) . '</span>';
            if ( $meta['venue_name'] ) {
                echo '<span class="wpec-list-venue">' . esc_html( $meta['venue_name'] ) . '</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // AJAX: fetch events JSON for calendar JS
    // ------------------------------------------------------------------
    public static function ajax_get_events(): void {
        if ( ! check_ajax_referer( 'wpec_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Security check failed', 403 );
        }

        $view     = sanitize_key( $_POST['view'] ?? 'month' );
        $year     = absint( $_POST['year'] ?? date( 'Y' ) );
        $month    = absint( $_POST['month'] ?? date( 'n' ) );
        $category = sanitize_text_field( $_POST['category'] ?? '' );
        $page     = absint( $_POST['page'] ?? 1 );
        // FIX BUG #7: clamp per_page — an attacker could pass 99999999 causing a DoS
        // on this unauthenticated public endpoint. Limit to a reasonable maximum.
        $per_page = min( 100, max( 1, absint( $_POST['per_page'] ?? get_option( 'wpec_events_per_page', 10 ) ) ) );

        $cache_key = WPEC_Cache::get_cache_key( "events_{$view}", compact( 'year', 'month', 'category', 'page', 'per_page' ) );
        $cached    = WPEC_Cache::get( $cache_key );
        if ( $cached !== false ) {
            wp_send_json_success( $cached );
        }

        $query_args = [
            'post_type'      => 'wpec_event',
            'post_status'    => 'publish',
            'meta_key'       => '_wpec_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ];

        // Date filters
        switch ( $view ) {
            case 'month':
                $start_of_month = sprintf( '%04d-%02d-01', $year, $month );
                $end_of_month   = date( 'Y-m-t', mktime( 0, 0, 0, $month, 1, $year ) );
                $query_args['meta_query'] = [ [
                    'key'     => '_wpec_start_date',
                    'value'   => [ $start_of_month, $end_of_month ],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ] ];
                $query_args['posts_per_page'] = -1;
                break;

            case 'week':
                $week_start = date( 'Y-m-d', strtotime( "monday this week", mktime( 0,0,0,$month,1,$year ) ) );
                $week_end   = date( 'Y-m-d', strtotime( '+6 days', strtotime( $week_start ) ) );
                $query_args['meta_query'] = [ [
                    'key'     => '_wpec_start_date',
                    'value'   => [ $week_start, $week_end ],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ] ];
                $query_args['posts_per_page'] = -1;
                break;

            case 'day':
                $day = absint( $_POST['day'] ?? date( 'j' ) );
                $date = sprintf( '%04d-%02d-%02d', $year, $month, $day );
                $query_args['meta_query'] = [ [
                    'key'     => '_wpec_start_date',
                    'value'   => $date,
                    'compare' => '=',
                    'type'    => 'DATE',
                ] ];
                $query_args['posts_per_page'] = -1;
                break;

            case 'photo':
                $query_args['meta_query'] = [ [
                    'key'     => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ], [
                    'key'     => '_wpec_start_date',
                    'value'   => date( 'Y-m-d' ),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ] ];
                $query_args['posts_per_page'] = $per_page;
                $query_args['paged']          = $page;
                break;

            default: // list, summary
                $query_args['meta_query'] = [ [
                    'key'     => '_wpec_start_date',
                    'value'   => date( 'Y-m-d' ),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ] ];
                $query_args['posts_per_page'] = $per_page;
                $query_args['paged']          = $page;
                break;
        }

        if ( $category ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'wpec_event_cat',
                'field'    => 'slug',
                'terms'    => $category,
            ] ];
        }

        $query  = new WP_Query( $query_args );
        $events = [];

        // FIX BUG #5: batch-load organizers in a single query to avoid N+1
        $post_ids = wp_list_pluck( $query->posts, 'ID' );
        $organizers_by_event = [];
        if ( ! empty( $post_ids ) ) {
            global $wpdb;
            $placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                "SELECT eo.event_id, o.id, o.name
                 FROM {$wpdb->prefix}wpec_organizers o
                 INNER JOIN {$wpdb->prefix}wpec_event_organizers eo ON o.id = eo.organizer_id
                 WHERE eo.event_id IN ($placeholders)",
                ...$post_ids
            ) );
            foreach ( $rows as $row ) {
                $organizers_by_event[ $row->event_id ][] = [ 'id' => $row->id, 'name' => $row->name ];
            }
        }

        foreach ( $query->posts as $post ) {
            $meta    = WPEC_Helpers::get_event_meta( $post->ID );
            $thumb   = get_the_post_thumbnail_url( $post->ID, 'medium' );
            $events[] = [
                'id'              => $post->ID,
                'title'           => $post->post_title,
                'excerpt'         => wp_trim_words( $post->post_content, 20 ),
                'url'             => get_permalink( $post->ID ),
                'thumbnail'       => $thumb ?: '',
                'start_date'      => $meta['start_date'],
                'start_time'      => $meta['start_time'],
                'end_date'        => $meta['end_date'],
                'end_time'        => $meta['end_time'],
                'all_day'         => $meta['all_day'],
                'start_formatted' => $meta['start_formatted'],
                'end_formatted'   => $meta['end_formatted'],
                'venue_name'      => $meta['venue_name'],
                'city'            => $meta['city'],
                'country'         => $meta['country'],
                'cost'            => $meta['cost'],
                'currency'        => $meta['currency'],
                'cost_formatted'  => $meta['cost'] ? WPEC_Helpers::format_price( (float) $meta['cost'], $meta['currency'] ) : __( 'Free', 'wp-events-calendar' ),
                'organizers'      => $organizers_by_event[ $post->ID ] ?? [],
                'booking_enabled' => $meta['booking_enabled'],
                'show_map'        => $meta['show_map'],
                'map_link'        => $meta['map_link'],
                'categories'      => wp_get_post_terms( $post->ID, 'wpec_event_cat', [ 'fields' => 'names' ] ),
            ];
        }

        $result = [
            'events'      => $events,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $page,
        ];

        WPEC_Cache::set( $cache_key, $result, 300 );
        wp_send_json_success( $result );
    }
}
