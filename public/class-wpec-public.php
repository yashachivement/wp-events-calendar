<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Public {

    public static function init(): void {
        add_action( 'wp_enqueue_scripts',      [ __CLASS__, 'enqueue_scripts' ] );
        add_filter( 'single_template',         [ __CLASS__, 'single_event_template' ] );
        add_filter( 'archive_template',        [ __CLASS__, 'archive_event_template' ] );
        add_action( 'wp_head',                 [ __CLASS__, 'output_theme_css_vars' ] );
        add_filter( 'the_content',             [ __CLASS__, 'maybe_append_booking_form' ] );
        add_action( 'template_redirect',         [ __CLASS__, 'handle_single_event_ical' ] );
    }

    public static function enqueue_scripts(): void {
        // FIX BUG #10: get_post() can return null; remove the duplicated
        // and fragile condition and use a single, clear check.
        global $post;
        $has_shortcode = $post && (
            has_shortcode( $post->post_content, 'wpec_calendar' ) ||
            has_shortcode( $post->post_content, 'wpec_events_list' )
        );
        if ( ! is_singular( 'wpec_event' ) && ! $has_shortcode && ! is_post_type_archive( 'wpec_event' ) ) {
            return;
        }

        wp_enqueue_style( 'wpec-public', WPEC_PLUGIN_URL . 'public/css/public.css', [], WPEC_VERSION );
        wp_enqueue_script( 'wpec-public', WPEC_PLUGIN_URL . 'public/js/calendar.js', [ 'jquery' ], WPEC_VERSION, true );
        wp_localize_script( 'wpec-public', 'wpecPublic', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'wpec_public_nonce' ),
            'bookingNonce' => wp_create_nonce( 'wpec_booking_nonce' ),
            'settings'   => WPEC_Settings::get_all(),
            'i18n'       => [
                'loading'    => __( 'Loading events…', 'wp-events-calendar' ),
                'noEvents'   => __( 'No events found.', 'wp-events-calendar' ),
                'bookNow'    => __( 'Book Now', 'wp-events-calendar' ),
                'free'       => __( 'Free', 'wp-events-calendar' ),
                'allDay'     => __( 'All Day', 'wp-events-calendar' ),
                'mon'        => __( 'Mon', 'wp-events-calendar' ),
                'tue'        => __( 'Tue', 'wp-events-calendar' ),
                'wed'        => __( 'Wed', 'wp-events-calendar' ),
                'thu'        => __( 'Thu', 'wp-events-calendar' ),
                'fri'        => __( 'Fri', 'wp-events-calendar' ),
                'sat'        => __( 'Sat', 'wp-events-calendar' ),
                'sun'        => __( 'Sun', 'wp-events-calendar' ),
                'months'     => [
                    __('January','wp-events-calendar'), __('February','wp-events-calendar'),
                    __('March','wp-events-calendar'),   __('April','wp-events-calendar'),
                    __('May','wp-events-calendar'),     __('June','wp-events-calendar'),
                    __('July','wp-events-calendar'),    __('August','wp-events-calendar'),
                    __('September','wp-events-calendar'),__('October','wp-events-calendar'),
                    __('November','wp-events-calendar'), __('December','wp-events-calendar'),
                ],
                'prev'       => __( '← Previous', 'wp-events-calendar' ),
                'next'       => __( 'Next →', 'wp-events-calendar' ),
                'today'      => __( 'Today', 'wp-events-calendar' ),
                'submitBooking' => __( 'Confirm Booking', 'wp-events-calendar' ),
                'bookingSuccess' => __( 'Your booking is confirmed!', 'wp-events-calendar' ),
                'requiredFields' => __( 'Please fill in all required fields.', 'wp-events-calendar' ),
            ],
        ] );

        // Enqueue Google Maps if API key set
        $api_key = get_option( 'wpec_google_maps_api_key', '' );
        if ( $api_key ) {
            wp_enqueue_script( 'google-maps', "https://maps.googleapis.com/maps/api/js?key={$api_key}", [], null, true );
        }

        // Stripe JS if enabled
        if ( get_option( 'wpec_stripe_enabled' ) ) {
            wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', [], null, true );
        }
    }

    public static function output_theme_css_vars(): void {
        // FIX (defense): sanitize_hex_color() is applied on save, but we also
        // validate on output — if value is not a valid hex color, fall back to
        // the safe default. This prevents CSS injection through any future
        // update_option() path or filter that bypasses the settings sanitizer.
        $defaults = [
            'wpec_theme_primary_color'   => '#3b82f6',
            'wpec_theme_secondary_color' => '#1e40af',
            'wpec_theme_text_color'      => '#1f2937',
            'wpec_theme_bg_color'        => '#ffffff',
        ];
        $colors = [];
        foreach ( $defaults as $key => $fallback ) {
            $value          = get_option( $key, $fallback );
            $colors[ $key ] = sanitize_hex_color( $value ) ?: $fallback;
        }
        printf(
            "<style>:root{--wpec-primary:%s;--wpec-secondary:%s;--wpec-text:%s;--wpec-bg:%s;}</style>\n",
            esc_attr( $colors['wpec_theme_primary_color'] ),
            esc_attr( $colors['wpec_theme_secondary_color'] ),
            esc_attr( $colors['wpec_theme_text_color'] ),
            esc_attr( $colors['wpec_theme_bg_color'] )
        );
    }

    public static function single_event_template( string $template ): string {
        if ( is_singular( 'wpec_event' ) ) {
            $plugin_template = WPEC_PLUGIN_DIR . 'public/views/single-event.php';
            $theme_template  = locate_template( 'wpec/single-event.php' );
            return $theme_template ?: $plugin_template;
        }
        return $template;
    }

    public static function archive_event_template( string $template ): string {
        if ( is_post_type_archive( 'wpec_event' ) ) {
            $plugin_template = WPEC_PLUGIN_DIR . 'public/views/archive-events.php';
            $theme_template  = locate_template( 'wpec/archive-events.php' );
            return $theme_template ?: $plugin_template;
        }
        return $template;
    }

    public static function maybe_append_booking_form( string $content ): string {
        if ( ! is_singular( 'wpec_event' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        $post_id        = get_the_ID();
        $meta           = WPEC_Helpers::get_event_meta( $post_id );
        $booking_global = get_option( 'wpec_booking_enabled' );

        if ( $booking_global && $meta['booking_enabled'] ) {
            ob_start();
            include WPEC_PLUGIN_DIR . 'public/views/booking-form.php';
            $content .= ob_get_clean();
        }

        // Additional content
        $additional = get_option( 'wpec_additional_content', '' );
        if ( $additional ) {
            $content .= '<div class="wpec-additional-content">' . wp_kses_post( $additional ) . '</div>';
        }

        return $content;
    }

    // FIX #5: Public per-event iCal download — no admin nonce required
    public static function handle_single_event_ical(): void {
        $event_id = absint( $_GET['wpec_event_ical'] ?? 0 );
        if ( ! $event_id ) return;

        $nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
        if ( ! wp_verify_nonce( $nonce, 'wpec_event_ical_' . $event_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'wp-events-calendar' ) );
        }

        $post = get_post( $event_id );
        if ( ! $post || $post->post_type !== 'wpec_event' || $post->post_status !== 'publish' ) {
            wp_die( esc_html__( 'Event not found.', 'wp-events-calendar' ) );
        }

        $meta = WPEC_Helpers::get_event_meta( $event_id );
        $tz   = get_option( 'wpec_timezone', wp_timezone_string() );
        $uid  = $event_id . '@' . wp_parse_url( get_site_url(), PHP_URL_HOST );

        header( 'Content-Type: text/calendar; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="event-' . $event_id . '.ics"' );
        header( 'Cache-Control: no-cache, must-revalidate' );

        $location = implode( ', ', array_filter( [
            $meta['venue_name'], $meta['address'], $meta['city'], $meta['state'], $meta['country'],
        ] ) );

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//WP Events Calendar//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "BEGIN:VEVENT\r\n";
        echo "UID:" . sanitize_text_field( $uid ) . "\r\n";
        echo "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
        echo "SUMMARY:" . self::ical_escape( $post->post_title ) . "\r\n";
        echo "DESCRIPTION:" . self::ical_escape( wp_strip_all_tags( $post->post_content ) ) . "\r\n";
        echo "URL:" . esc_url( get_permalink( $event_id ) ) . "\r\n";
        if ( $location ) {
            echo "LOCATION:" . self::ical_escape( $location ) . "\r\n";
        }
        if ( $meta['all_day'] ) {
            echo "DTSTART;VALUE=DATE:" . str_replace( '-', '', $meta['start_date'] ) . "\r\n";
            echo "DTEND;VALUE=DATE:"   . str_replace( '-', '', $meta['end_date'] ?: $meta['start_date'] ) . "\r\n";
        } else {
            $start = $meta['start_date'] . 'T' . ( $meta['start_time'] ?: '00:00' ) . ':00';
            $end   = ( $meta['end_date'] ?: $meta['start_date'] ) . 'T' . ( $meta['end_time'] ?: '23:59' ) . ':00';
            echo "DTSTART;TZID={$tz}:" . str_replace( [ '-', ':' ], '', $start ) . "\r\n";
            echo "DTEND;TZID={$tz}:"   . str_replace( [ '-', ':' ], '', $end )   . "\r\n";
        }
        echo "END:VEVENT\r\n";
        echo "END:VCALENDAR\r\n";
        exit;
    }

    private static function ical_escape( string $str ): string {
        return str_replace( [ '\\', "\n", ';', ',' ], [ '\\\\', '\\n', '\\;', '\\,' ], $str );
    }
}
