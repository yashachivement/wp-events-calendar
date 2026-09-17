<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Import_Export {

    public static function init(): void {
        add_action( 'wp_ajax_wpec_export_csv',    [ __CLASS__, 'export_csv' ] );
        add_action( 'wp_ajax_wpec_export_ical',   [ __CLASS__, 'export_ical' ] );
        add_action( 'wp_ajax_wpec_import_csv',    [ __CLASS__, 'import_csv' ] );
        add_action( 'wp_ajax_wpec_import_ical',   [ __CLASS__, 'import_ical' ] );
        // Public iCal feed
        add_action( 'init', [ __CLASS__, 'register_ical_endpoint' ] );
        add_action( 'template_redirect', [ __CLASS__, 'handle_ical_feed' ] );
    }

    // ------------------------------------------------------------------
    // Export CSV
    // ------------------------------------------------------------------
    public static function export_csv(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );

        $events = get_posts( [
            'post_type'      => 'wpec_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => '_wpec_start_date',
            'order'          => 'ASC',
        ] );

        $filename = 'wp-events-' . date( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        // BOM for Excel UTF-8
        fputs( $output, "\xEF\xBB\xBF" );

        fputcsv( $output, [
            'ID', 'Title', 'Description', 'Start Date', 'Start Time',
            'End Date', 'End Time', 'All Day', 'Venue', 'Address',
            'City', 'State', 'Country', 'Website', 'Phone',
            'Cost', 'Currency', 'Status', 'URL',
        ] );

        foreach ( $events as $event ) {
            $meta = WPEC_Helpers::get_event_meta( $event->ID );
            fputcsv( $output, [
                $event->ID,
                $event->post_title,
                wp_strip_all_tags( $event->post_content ),
                $meta['start_date'],
                $meta['start_time'],
                $meta['end_date'],
                $meta['end_time'],
                $meta['all_day'] ? 'Yes' : 'No',
                $meta['venue_name'],
                $meta['address'],
                $meta['city'],
                $meta['state'],
                $meta['country'],
                $meta['website'],
                $meta['phone'],
                $meta['cost'],
                $meta['currency'],
                $event->post_status,
                get_permalink( $event->ID ),
            ] );
        }

        fclose( $output );
        exit;
    }

    // ------------------------------------------------------------------
    // Export iCal
    // ------------------------------------------------------------------
    public static function export_ical(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        self::output_ical();
        exit;
    }

    public static function register_ical_endpoint(): void {
        add_rewrite_rule( '^events\.ics$', 'index.php?wpec_ical=1', 'top' );
        add_rewrite_tag( '%wpec_ical%', '([0-9]+)' );
    }

    public static function handle_ical_feed(): void {
        if ( get_query_var( 'wpec_ical' ) ) {
            self::output_ical();
            exit;
        }
    }

    private static function output_ical(): void {
        $events = get_posts( [
            'post_type'      => 'wpec_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ] );

        $tz = get_option( 'wpec_timezone', wp_timezone_string() );

        header( 'Content-Type: text/calendar; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="events.ics"' );

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//WP Events Calendar//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        // FIX BUG #11: strip CR/LF from values to prevent iCal line injection
        $cal_name = str_replace( [ "\r", "\n" ], '', get_bloginfo( 'name' ) );
        $safe_tz  = str_replace( [ "\r", "\n" ], '', $tz );
        echo "X-WR-CALNAME:" . $cal_name . " Events\r\n";
        echo "X-WR-TIMEZONE:" . $safe_tz . "\r\n";

        foreach ( $events as $event ) {
            $meta = WPEC_Helpers::get_event_meta( $event->ID );
            $uid  = $event->ID . '@' . parse_url( get_site_url(), PHP_URL_HOST );
            $dtstamp = gmdate( 'Ymd\THis\Z' );

            echo "BEGIN:VEVENT\r\n";
            echo "UID:{$uid}\r\n";
            echo "DTSTAMP:{$dtstamp}\r\n";
            echo "SUMMARY:" . self::ical_escape( $event->post_title ) . "\r\n";
            echo "DESCRIPTION:" . self::ical_escape( wp_strip_all_tags( $event->post_content ) ) . "\r\n";
            echo "URL:" . get_permalink( $event->ID ) . "\r\n";

            if ( $meta['all_day'] ) {
                echo "DTSTART;VALUE=DATE:" . str_replace( '-', '', $meta['start_date'] ) . "\r\n";
                echo "DTEND;VALUE=DATE:" . str_replace( '-', '', $meta['end_date'] ?: $meta['start_date'] ) . "\r\n";
            } else {
                $start = $meta['start_date'] . 'T' . ( $meta['start_time'] ?: '00:00' ) . ':00';
                $end   = ( $meta['end_date'] ?: $meta['start_date'] ) . 'T' . ( $meta['end_time'] ?: '23:59' ) . ':00';
                echo "DTSTART;TZID={$tz}:" . str_replace( [ '-', ':' ], '', $start ) . "\r\n";
                echo "DTEND;TZID={$tz}:" . str_replace( [ '-', ':' ], '', $end ) . "\r\n";
            }

            $location = implode( ', ', array_filter( [ $meta['venue_name'], $meta['address'], $meta['city'], $meta['country'] ] ) );
            if ( $location ) {
                echo "LOCATION:" . self::ical_escape( $location ) . "\r\n";
            }

            echo "END:VEVENT\r\n";
        }

        echo "END:VCALENDAR\r\n";
    }

    private static function ical_escape( string $str ): string {
        return str_replace(
            [ '\\', "\n", ';', ',' ],
            [ '\\\\', '\n', '\;', '\,' ],
            $str
        );
    }

    // ------------------------------------------------------------------
    // Import CSV
    // ------------------------------------------------------------------
    public static function import_csv(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        if ( ! isset( $_FILES['import_file'] ) ) {
            wp_send_json_error( __( 'No file uploaded.', 'wp-events-calendar' ) );
        }

        $file = $_FILES['import_file'];
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( __( 'Upload error.', 'wp-events-calendar' ) );
        }

        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( $ext !== 'csv' ) {
            wp_send_json_error( __( 'Please upload a CSV file.', 'wp-events-calendar' ) );
        }

        $handle  = fopen( $file['tmp_name'], 'r' );
        $headers = fgetcsv( $handle );

        // Skip BOM
        if ( $headers && isset( $headers[0] ) ) {
            $headers[0] = ltrim( $headers[0], "\xEF\xBB\xBF" );
        }

        $imported = 0;
        $errors   = [];

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            // FIX BUG #1: array_combine throws ValueError in PHP 8 when column
            // counts differ. Check counts first and skip mismatched rows gracefully.
            if ( count( $headers ) !== count( $row ) ) {
                $errors[] = sprintf(
                    __( 'Skipped row: expected %d columns, got %d.', 'wp-events-calendar' ),
                    count( $headers ), count( $row )
                );
                continue;
            }
            $data = array_combine( $headers, $row );
            if ( ! $data ) continue;

            $title = sanitize_text_field( $data['Title'] ?? '' );
            if ( ! $title ) {
                $errors[] = __( 'Skipped row: missing title.', 'wp-events-calendar' );
                continue;
            }

            $post_id = wp_insert_post( [
                'post_title'   => $title,
                'post_content' => wp_kses_post( $data['Description'] ?? '' ),
                'post_status'  => 'publish',
                'post_type'    => 'wpec_event',
            ] );

            if ( is_wp_error( $post_id ) ) {
                $errors[] = $post_id->get_error_message();
                continue;
            }

            $field_map = [
                'Start Date' => '_wpec_start_date', 'Start Time' => '_wpec_start_time',
                'End Date'   => '_wpec_end_date',   'End Time'   => '_wpec_end_time',
                'Venue'      => '_wpec_venue_name', 'Address'    => '_wpec_address',
                'City'       => '_wpec_city',       'State'      => '_wpec_state',
                'Country'    => '_wpec_country',    'Website'    => '_wpec_website',
                'Phone'      => '_wpec_phone',      'Cost'       => '_wpec_cost',
                'Currency'   => '_wpec_currency',
            ];

            foreach ( $field_map as $csv_col => $meta_key ) {
                if ( isset( $data[ $csv_col ] ) && $data[ $csv_col ] !== '' ) {
                    update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $csv_col ] ) );
                }
            }

            if ( isset( $data['All Day'] ) && strtolower( $data['All Day'] ) === 'yes' ) {
                update_post_meta( $post_id, '_wpec_all_day', '1' );
            }

            $imported++;
        }

        fclose( $handle );
        WPEC_Cache::flush_calendar_caches();
        wp_send_json_success( [
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => sprintf( __( 'Imported %d events.', 'wp-events-calendar' ), $imported ),
        ] );
    }

    // ------------------------------------------------------------------
    // Import iCal
    // ------------------------------------------------------------------
    public static function import_ical(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        if ( ! isset( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( __( 'Upload error.', 'wp-events-calendar' ) );
        }

        $content  = file_get_contents( $_FILES['import_file']['tmp_name'] );
        $events   = self::parse_ical( $content );
        $imported = 0;

        foreach ( $events as $event ) {
            $post_id = wp_insert_post( [
                'post_title'   => sanitize_text_field( $event['summary'] ?? 'Untitled Event' ),
                'post_content' => wp_kses_post( $event['description'] ?? '' ),
                'post_status'  => 'publish',
                'post_type'    => 'wpec_event',
            ] );

            if ( is_wp_error( $post_id ) ) continue;

            if ( ! empty( $event['start_date'] ) ) {
                update_post_meta( $post_id, '_wpec_start_date', $event['start_date'] );
                update_post_meta( $post_id, '_wpec_start_time', $event['start_time'] ?? '' );
            }
            if ( ! empty( $event['end_date'] ) ) {
                update_post_meta( $post_id, '_wpec_end_date', $event['end_date'] );
                update_post_meta( $post_id, '_wpec_end_time', $event['end_time'] ?? '' );
            }
            if ( ! empty( $event['location'] ) ) {
                update_post_meta( $post_id, '_wpec_venue_name', sanitize_text_field( $event['location'] ) );
            }
            if ( ! empty( $event['all_day'] ) ) {
                update_post_meta( $post_id, '_wpec_all_day', '1' );
            }

            $imported++;
        }

        WPEC_Cache::flush_calendar_caches();
        wp_send_json_success( [
            'imported' => $imported,
            'message'  => sprintf( __( 'Imported %d events from iCal.', 'wp-events-calendar' ), $imported ),
        ] );
    }

    private static function parse_ical( string $content ): array {
        $events    = [];
        $in_event  = false;
        $current   = [];

        $lines = preg_split( '/\r\n|\r|\n/', $content );

        // Unfold lines (RFC 5545)
        $unfolded = [];
        foreach ( $lines as $line ) {
            if ( isset( $unfolded[ count( $unfolded ) - 1 ] ) && str_starts_with( $line, ' ' ) ) {
                $unfolded[ count( $unfolded ) - 1 ] .= ltrim( $line );
            } else {
                $unfolded[] = $line;
            }
        }

        foreach ( $unfolded as $line ) {
            $line = trim( $line );
            if ( $line === 'BEGIN:VEVENT' ) {
                $in_event = true;
                $current  = [];
                continue;
            }
            if ( $line === 'END:VEVENT' ) {
                $in_event = false;
                if ( ! empty( $current ) ) $events[] = $current;
                continue;
            }
            if ( ! $in_event ) continue;

            [ $key, $value ] = array_pad( explode( ':', $line, 2 ), 2, '' );
            $key_clean = strtoupper( preg_replace( '/;.*/', '', $key ) );

            switch ( $key_clean ) {
                case 'SUMMARY':
                    $current['summary'] = self::ical_unescape( $value );
                    break;
                case 'DESCRIPTION':
                    $current['description'] = self::ical_unescape( $value );
                    break;
                case 'LOCATION':
                    $current['location'] = self::ical_unescape( $value );
                    break;
                case 'DTSTART':
                    $parsed = self::parse_ical_date( $key, $value );
                    $current['start_date'] = $parsed['date'];
                    $current['start_time'] = $parsed['time'];
                    $current['all_day']    = $parsed['all_day'];
                    break;
                case 'DTEND':
                    $parsed = self::parse_ical_date( $key, $value );
                    $current['end_date'] = $parsed['date'];
                    $current['end_time'] = $parsed['time'];
                    break;
            }
        }

        return $events;
    }

    private static function parse_ical_date( string $key, string $value ): array {
        $all_day = str_contains( $key, 'VALUE=DATE' );
        if ( $all_day ) {
            return [
                'date'    => substr( $value, 0, 4 ) . '-' . substr( $value, 4, 2 ) . '-' . substr( $value, 6, 2 ),
                'time'    => '',
                'all_day' => true,
            ];
        }
        // Parse YYYYMMDDTHHMMSS or YYYYMMDDTHHMMSSZ
        $ts = strtotime( $value );
        return [
            'date'    => $ts ? date( 'Y-m-d', $ts ) : '',
            'time'    => $ts ? date( 'H:i', $ts ) : '',
            'all_day' => false,
        ];
    }

    private static function ical_unescape( string $str ): string {
        return str_replace( [ '\n', '\;', '\,', '\\\\' ], [ "\n", ';', ',', '\\' ], $str );
    }
}
