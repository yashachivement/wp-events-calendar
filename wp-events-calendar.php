<?php
/**
 * Plugin Name:       WP Events Calendar – Event Management & Booking by Yash
 * Plugin URI:        https://github.com/yashachivement/wp-events-calendar
 * Description:       A powerful, modern WordPress events calendar plugin featuring booking & ticket capacity, PayPal & Stripe payment gateways, Google Calendar 2-way sync, 6 calendar views, 3 templates, and import/export.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.3
 * Author:            Yash
 * Author URI:        https://yashwebdesigner.in
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-events-calendar
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WPEC_VERSION',      '1.0.1' );
define( 'WPEC_PLUGIN_FILE',  __FILE__ );
define( 'WPEC_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'WPEC_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'WPEC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Minimum requirements
define( 'WPEC_MIN_PHP',  '8.3' );
define( 'WPEC_MIN_WP',   '6.0' );

/**
 * Check requirements before loading.
 */
function wpec_check_requirements(): bool {
    if ( version_compare( PHP_VERSION, WPEC_MIN_PHP, '<' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                sprintf(
                    /* translators: %s: minimum PHP version */
                    esc_html__( 'WP Events Calendar requires PHP %s or higher.', 'wp-events-calendar' ),
                    WPEC_MIN_PHP
                ) .
                '</p></div>';
        } );
        return false;
    }

    global $wp_version;
    if ( version_compare( $wp_version, WPEC_MIN_WP, '<' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                sprintf(
                    /* translators: %s: minimum WP version */
                    esc_html__( 'WP Events Calendar requires WordPress %s or higher.', 'wp-events-calendar' ),
                    WPEC_MIN_WP
                ) .
                '</p></div>';
        } );
        return false;
    }

    return true;
}

/**
 * Main plugin bootstrap.
 */
function wpec_init(): void {
    if ( ! wpec_check_requirements() ) {
        return;
    }

    // Load text domain
    load_plugin_textdomain( 'wp-events-calendar', false, dirname( WPEC_PLUGIN_BASENAME ) . '/languages' );

    // Load core files
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-helpers.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-post-type.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-taxonomies.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-meta-boxes.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-settings.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-shortcode.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-cache.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-google-calendar.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-import-export.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-booking.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-payment.php';
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-security.php';
    require_once WPEC_PLUGIN_DIR . 'admin/class-wpec-admin.php';
    require_once WPEC_PLUGIN_DIR . 'public/class-wpec-public.php';

    // Boot components
    WPEC_Post_Type::init();
    WPEC_Taxonomies::init();
    WPEC_Meta_Boxes::init();
    WPEC_Settings::init();
    WPEC_Shortcode::init();
    WPEC_Cache::init();
    WPEC_Google_Calendar::init();
    WPEC_Import_Export::init();
    WPEC_Booking::init();
    WPEC_Payment::init();
    WPEC_Security::init();
    WPEC_Admin::init();
    WPEC_Public::init();
}
add_action( 'plugins_loaded', 'wpec_init' );

/**
 * Activation hook.
 */
function wpec_activate(): void {
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-activator.php';
    WPEC_Activator::activate();
}
register_activation_hook( __FILE__, 'wpec_activate' );

/**
 * Deactivation hook.
 */
function wpec_deactivate(): void {
    require_once WPEC_PLUGIN_DIR . 'includes/class-wpec-activator.php';
    WPEC_Activator::deactivate();
}
register_deactivation_hook( __FILE__, 'wpec_deactivate' );
