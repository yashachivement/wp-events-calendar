<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Cache {

    private static string $group = 'wpec_events';

    public static function init(): void {
        add_action( 'save_post_wpec_event', [ __CLASS__, 'clear_event_cache' ] );
        add_action( 'delete_post',          [ __CLASS__, 'clear_event_cache' ] );
        add_action( 'wp_ajax_wpec_clear_cache', [ __CLASS__, 'ajax_clear_all_cache' ] );
    }

    public static function get( string $key ): mixed {
        return wp_cache_get( $key, self::$group );
    }

    public static function set( string $key, mixed $data, int $expire = 3600 ): bool {
        return wp_cache_set( $key, $data, self::$group, $expire );
    }

    public static function delete( string $key ): bool {
        return wp_cache_delete( $key, self::$group );
    }

    public static function clear_event_cache( int $post_id ): void {
        wp_cache_delete( 'event_meta_' . $post_id, self::$group );
        wp_cache_delete( 'event_organizers_' . $post_id, self::$group );
        self::flush_calendar_caches();
    }

    public static function flush_calendar_caches(): void {
        // Increment a version key so all cache keys derived from it are invalidated.
        $version = (int) get_option( 'wpec_cache_version', 1 );
        update_option( 'wpec_cache_version', $version + 1, false );

        // FIX: wp_cache_flush_group() was added in WP 6.1 but the plugin
        // declares Requires at least: 6.0, so guard with function_exists().
        // On persistent object-cache hosts this gives proper group flushing;
        // on the default non-persistent cache the version bump above is enough.
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( self::$group );
        }
    }

    public static function ajax_clear_all_cache(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'wp-events-calendar' ) );
        }
        self::flush_calendar_caches();
        wp_send_json_success( __( 'Cache cleared successfully.', 'wp-events-calendar' ) );
    }

    public static function get_cache_key( string $prefix, array $args = [] ): string {
        $version = get_option( 'wpec_cache_version', 1 );
        return $prefix . '_v' . $version . '_' . md5( serialize( $args ) );
    }
}
