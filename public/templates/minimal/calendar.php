<?php
/**
 * Classic calendar template — PHP shell.
 * The heavy lifting is done in calendar.js; this file sets up the HTML mount point.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$settings   = WPEC_Settings::get_all();
$categories = get_terms( [ 'taxonomy' => 'wpec_event_cat', 'hide_empty' => true ] );
$views      = [ 'month', 'week', 'day', 'list', 'summary', 'photo' ];
?>
<div class="wpec-calendar-wrap wpec-template-<?php echo esc_attr( $atts['template'] ?? 'classic' ); ?>"
     data-view="<?php echo esc_attr( $view ); ?>"
     data-template="<?php echo esc_attr( $atts['template'] ?? 'classic' ); ?>"
     data-per-page="<?php echo esc_attr( $per_page ); ?>"
     data-category="<?php echo esc_attr( $category ); ?>">

    <!-- Toolbar -->
    <div class="wpec-toolbar">
        <div class="wpec-toolbar-left">
            <button class="wpec-btn wpec-btn-outline wpec-nav-prev" aria-label="<?php esc_attr_e( 'Previous', 'wp-events-calendar' ); ?>">&#8592;</button>
            <button class="wpec-btn wpec-btn-outline wpec-nav-today"><?php esc_html_e( 'Today', 'wp-events-calendar' ); ?></button>
            <button class="wpec-btn wpec-btn-outline wpec-nav-next" aria-label="<?php esc_attr_e( 'Next', 'wp-events-calendar' ); ?>">&#8594;</button>
        </div>

        <div class="wpec-toolbar-center">
            <h2 class="wpec-current-label"></h2>
        </div>

        <div class="wpec-toolbar-right">
            <!-- View switcher -->
            <?php if ( ! empty( $settings['show_view_switcher'] ) ) : ?>
            <div class="wpec-view-switcher" role="group" aria-label="<?php esc_attr_e( 'Calendar view', 'wp-events-calendar' ); ?>">
                <?php foreach ( $views as $v ) : ?>
                    <button class="wpec-view-btn <?php echo $v === $view ? 'active' : ''; ?>"
                            data-view="<?php echo esc_attr( $v ); ?>">
                        <?php echo esc_html( ucfirst( $v ) ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Category filter -->
            <?php if ( ! empty( $settings['show_cat_filter'] ) && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
            <select class="wpec-cat-filter" aria-label="<?php esc_attr_e( 'Filter by category', 'wp-events-calendar' ); ?>">
                <option value=""><?php esc_html_e( 'All Categories', 'wp-events-calendar' ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </div>

    <!-- Calendar body — filled by calendar.js -->
    <div class="wpec-calendar-body">
        <div class="wpec-loading">
            <span class="wpec-spinner"></span>
            <?php esc_html_e( 'Loading events…', 'wp-events-calendar' ); ?>
        </div>
    </div>

    <!-- Pagination (list/summary/photo views) -->
    <div class="wpec-pagination" style="display:none;"></div>

    <!-- Event detail modal -->
    <div class="wpec-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Event details', 'wp-events-calendar' ); ?>">
        <div class="wpec-modal">
            <button class="wpec-modal-close" aria-label="<?php esc_attr_e( 'Close', 'wp-events-calendar' ); ?>">&times;</button>
            <div class="wpec-modal-body"></div>
        </div>
    </div>

</div>
