<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpec-admin-wrap">
    <h1><?php esc_html_e( 'Shortcode Generator', 'wp-events-calendar' ); ?></h1>
    <p><?php esc_html_e( 'Configure the options below and copy the generated shortcode into any page or post.', 'wp-events-calendar' ); ?></p>

    <div class="wpec-two-col">
        <div class="wpec-col">
            <div class="wpec-card">
                <h2><?php esc_html_e( 'Calendar Options', 'wp-events-calendar' ); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><label for="sg_view"><?php esc_html_e( 'Default View', 'wp-events-calendar' ); ?></label></th>
                        <td>
                            <select id="sg_view" class="wpec-sg-field">
                                <option value="month">Month</option>
                                <option value="week">Week</option>
                                <option value="day">Day</option>
                                <option value="list">List</option>
                                <option value="summary">Summary</option>
                                <option value="photo">Photo</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sg_template"><?php esc_html_e( 'Template', 'wp-events-calendar' ); ?></label></th>
                        <td>
                            <select id="sg_template" class="wpec-sg-field">
                                <option value="classic">Classic</option>
                                <option value="modern">Modern</option>
                                <option value="minimal">Minimal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sg_per_page"><?php esc_html_e( 'Events Per Page', 'wp-events-calendar' ); ?></label></th>
                        <td><input type="number" id="sg_per_page" class="wpec-sg-field small-text" value="10" min="1" max="100" /></td>
                    </tr>
                    <tr>
                        <th><label for="sg_category"><?php esc_html_e( 'Filter by Category', 'wp-events-calendar' ); ?></label></th>
                        <td>
                            <select id="sg_category" class="wpec-sg-field">
                                <option value=""><?php esc_html_e( 'All Categories', 'wp-events-calendar' ); ?></option>
                                <?php
                                $cats = get_terms( [ 'taxonomy' => 'wpec_event_cat', 'hide_empty' => false ] );
                                foreach ( $cats as $cat ) :
                                ?>
                                    <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="wpec-col">
            <div class="wpec-card">
                <h2><?php esc_html_e( 'Generated Shortcode', 'wp-events-calendar' ); ?></h2>
                <div class="wpec-shortcode-output">
                    <code id="wpec-shortcode-preview">[wpec_calendar]</code>
                    <button type="button" id="wpec-copy-shortcode" class="button button-primary">
                        <?php esc_html_e( 'Copy Shortcode', 'wp-events-calendar' ); ?>
                    </button>
                </div>
                <p class="description"><?php esc_html_e( 'Paste this shortcode into any page, post, or widget.', 'wp-events-calendar' ); ?></p>

                <hr>
                <h3><?php esc_html_e( 'Simple Events List Shortcode', 'wp-events-calendar' ); ?></h3>
                <p><?php esc_html_e( 'To show a simple upcoming events list:', 'wp-events-calendar' ); ?></p>
                <code>[wpec_events_list limit="5" upcoming="true"]</code>
                <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpec_events_list limit=\'5\' upcoming=\'true\']')"><?php esc_html_e( 'Copy', 'wp-events-calendar' ); ?></button>

                <hr>
                <h3><?php esc_html_e( 'Shortcode Reference', 'wp-events-calendar' ); ?></h3>
                <table class="widefat">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'wp-events-calendar' ); ?></th><th><?php esc_html_e( 'Values', 'wp-events-calendar' ); ?></th><th><?php esc_html_e( 'Default', 'wp-events-calendar' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>view</code></td><td>month, week, day, list, summary, photo</td><td>month</td></tr>
                        <tr><td><code>template</code></td><td>classic, modern, minimal</td><td>classic</td></tr>
                        <tr><td><code>per_page</code></td><td>1–100</td><td>10</td></tr>
                        <tr><td><code>category</code></td><td><?php esc_html_e( 'category slug', 'wp-events-calendar' ); ?></td><td><?php esc_html_e( '(all)', 'wp-events-calendar' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    function updateShortcode() {
        var view     = $('#sg_view').val();
        var template = $('#sg_template').val();
        var perPage  = $('#sg_per_page').val();
        var category = $('#sg_category').val();
        var sc = '[wpec_calendar';
        if (view && view !== 'month')         sc += ' view="' + view + '"';
        if (template && template !== 'classic') sc += ' template="' + template + '"';
        if (perPage && perPage !== '10')       sc += ' per_page="' + perPage + '"';
        if (category)                          sc += ' category="' + category + '"';
        sc += ']';
        $('#wpec-shortcode-preview').text(sc);
    }

    $('.wpec-sg-field').on('change input', updateShortcode);

    $('#wpec-copy-shortcode').on('click', function() {
        var text = $('#wpec-shortcode-preview').text();
        navigator.clipboard.writeText(text).then(() => {
            $(this).text('<?php esc_html_e( 'Copied!', 'wp-events-calendar' ); ?>');
            setTimeout(() => $(this).text('<?php esc_html_e( 'Copy Shortcode', 'wp-events-calendar' ); ?>'), 2000);
        });
    });
})(jQuery);
</script>
