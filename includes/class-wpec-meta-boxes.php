<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Meta_Boxes {

    public static function init(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_wpec_event', [ __CLASS__, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    public static function enqueue_scripts( string $hook ): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'wpec_event' ) return;

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', [], '1.13.2' );
        wp_enqueue_media();
        wp_enqueue_script(
            'wpec-admin-meta',
            WPEC_PLUGIN_URL . 'admin/js/meta-boxes.js',
            [ 'jquery', 'jquery-ui-datepicker' ],
            WPEC_VERSION,
            true
        );
        wp_enqueue_style( 'wpec-admin-meta', WPEC_PLUGIN_URL . 'admin/css/admin.css', [], WPEC_VERSION );
        wp_localize_script( 'wpec-admin-meta', 'wpecMeta', [
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'wpec_meta_nonce' ),
            'addOrganizerText' => __( 'Add Organizer', 'wp-events-calendar' ),
            'removeText'     => __( 'Remove', 'wp-events-calendar' ),
            'selectImage'    => __( 'Select Image', 'wp-events-calendar' ),
            'useImage'       => __( 'Use this image', 'wp-events-calendar' ),
        ] );
    }

    public static function add_meta_boxes(): void {
        add_meta_box( 'wpec_event_details', __( 'Event Details', 'wp-events-calendar' ), [ __CLASS__, 'render_event_details' ], 'wpec_event', 'normal', 'high' );
        add_meta_box( 'wpec_event_location', __( 'Location & Venue', 'wp-events-calendar' ), [ __CLASS__, 'render_location' ], 'wpec_event', 'normal', 'default' );
        add_meta_box( 'wpec_event_organizers', __( 'Organizers', 'wp-events-calendar' ), [ __CLASS__, 'render_organizers' ], 'wpec_event', 'normal', 'default' );
        add_meta_box( 'wpec_event_cost', __( 'Event Cost & Booking', 'wp-events-calendar' ), [ __CLASS__, 'render_cost_booking' ], 'wpec_event', 'normal', 'default' );
        add_meta_box( 'wpec_event_links', __( 'Website & Links', 'wp-events-calendar' ), [ __CLASS__, 'render_links' ], 'wpec_event', 'side', 'default' );
        add_meta_box( 'wpec_event_google', __( 'Google Calendar', 'wp-events-calendar' ), [ __CLASS__, 'render_google_calendar' ], 'wpec_event', 'side', 'default' );
    }

    // -------------------------------------------------------------------------
    // Event Details
    // -------------------------------------------------------------------------
    public static function render_event_details( \WP_Post $post ): void {
        wp_nonce_field( 'wpec_save_event_meta', 'wpec_event_meta_nonce' );
        $start_date  = get_post_meta( $post->ID, '_wpec_start_date', true );
        $start_time  = get_post_meta( $post->ID, '_wpec_start_time', true );
        $end_date    = get_post_meta( $post->ID, '_wpec_end_date', true );
        $end_time    = get_post_meta( $post->ID, '_wpec_end_time', true );
        $all_day     = get_post_meta( $post->ID, '_wpec_all_day', true );
        $recurrence  = get_post_meta( $post->ID, '_wpec_recurrence', true );
        ?>
        <div class="wpec-meta-section">
            <table class="wpec-meta-table">
                <tr>
                    <th><label><?php esc_html_e( 'All Day Event', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <input type="checkbox" name="wpec_all_day" id="wpec_all_day" value="1" <?php checked( $all_day, '1' ); ?> />
                        <label for="wpec_all_day"><?php esc_html_e( 'This is an all-day event', 'wp-events-calendar' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="wpec_start_date"><?php esc_html_e( 'Start Date', 'wp-events-calendar' ); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="date" name="wpec_start_date" id="wpec_start_date" value="<?php echo esc_attr( $start_date ); ?>" required class="wpec-date-field" />
                        <span class="wpec-time-fields" <?php echo $all_day ? 'style="display:none"' : ''; ?>>
                            <input type="time" name="wpec_start_time" id="wpec_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="wpec-time-field" />
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><label for="wpec_end_date"><?php esc_html_e( 'End Date', 'wp-events-calendar' ); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="date" name="wpec_end_date" id="wpec_end_date" value="<?php echo esc_attr( $end_date ); ?>" required class="wpec-date-field" />
                        <span class="wpec-time-fields" <?php echo $all_day ? 'style="display:none"' : ''; ?>>
                            <input type="time" name="wpec_end_time" id="wpec_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="wpec-time-field" />
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><label for="wpec_recurrence"><?php esc_html_e( 'Recurrence', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <select name="wpec_recurrence" id="wpec_recurrence">
                            <option value="" <?php selected( $recurrence, '' ); ?>><?php esc_html_e( 'No recurrence', 'wp-events-calendar' ); ?></option>
                            <option value="daily" <?php selected( $recurrence, 'daily' ); ?>><?php esc_html_e( 'Daily', 'wp-events-calendar' ); ?></option>
                            <option value="weekly" <?php selected( $recurrence, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wp-events-calendar' ); ?></option>
                            <option value="monthly" <?php selected( $recurrence, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wp-events-calendar' ); ?></option>
                            <option value="yearly" <?php selected( $recurrence, 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'wp-events-calendar' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Location & Venue
    // -------------------------------------------------------------------------
    public static function render_location( \WP_Post $post ): void {
        $venue        = get_post_meta( $post->ID, '_wpec_venue_name', true );
        $address      = get_post_meta( $post->ID, '_wpec_address', true );
        $city         = get_post_meta( $post->ID, '_wpec_city', true );
        $state        = get_post_meta( $post->ID, '_wpec_state', true );
        $country      = get_post_meta( $post->ID, '_wpec_country', true );
        $show_map     = get_post_meta( $post->ID, '_wpec_show_map', true );
        $map_link     = get_post_meta( $post->ID, '_wpec_map_link', true );
        ?>
        <div class="wpec-meta-section">
            <table class="wpec-meta-table">
                <tr>
                    <th><label for="wpec_venue_name"><?php esc_html_e( 'Venue Name', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" name="wpec_venue_name" id="wpec_venue_name" value="<?php echo esc_attr( $venue ); ?>" class="widefat" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_address"><?php esc_html_e( 'Street Address', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" name="wpec_address" id="wpec_address" value="<?php echo esc_attr( $address ); ?>" class="widefat" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_city"><?php esc_html_e( 'City', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" name="wpec_city" id="wpec_city" value="<?php echo esc_attr( $city ); ?>" class="widefat" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_state"><?php esc_html_e( 'State / Province', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" name="wpec_state" id="wpec_state" value="<?php echo esc_attr( $state ); ?>" class="widefat" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_country"><?php esc_html_e( 'Country', 'wp-events-calendar' ); ?></label></th>
                    <td><?php self::render_country_select( $country ); ?></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Map (Optional)', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_map" value="1" <?php checked( $show_map, '1' ); ?> />
                            <?php esc_html_e( 'Show map on event page', 'wp-events-calendar' ); ?>
                        </label><br>
                        <label for="wpec_map_link"><?php esc_html_e( 'Map Link (Google Maps URL)', 'wp-events-calendar' ); ?></label>
                        <input type="url" name="wpec_map_link" id="wpec_map_link" value="<?php echo esc_url( $map_link ); ?>" class="widefat" placeholder="https://maps.google.com/..." />
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Organizers
    // -------------------------------------------------------------------------
    public static function render_organizers( \WP_Post $post ): void {
        global $wpdb;
        $all_organizers     = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpec_organizers ORDER BY name ASC" );
        $selected_ids       = $wpdb->get_col( $wpdb->prepare(
            "SELECT organizer_id FROM {$wpdb->prefix}wpec_event_organizers WHERE event_id = %d",
            $post->ID
        ) );
        ?>
        <div class="wpec-meta-section">
            <div id="wpec-organizers-list">
                <?php foreach ( $selected_ids as $oid ) :
                    $org = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpec_organizers WHERE id = %d", $oid ) );
                    if ( ! $org ) continue;
                    ?>
                    <div class="wpec-organizer-row" data-id="<?php echo esc_attr( $org->id ); ?>">
                        <span class="wpec-organizer-name"><?php echo esc_html( $org->name ); ?></span>
                        <input type="hidden" name="wpec_organizers[]" value="<?php echo esc_attr( $org->id ); ?>" />
                        <button type="button" class="wpec-remove-organizer button-link-delete"><?php esc_html_e( 'Remove', 'wp-events-calendar' ); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="wpec-add-organizer-wrap">
                <select id="wpec-organizer-select">
                    <option value=""><?php esc_html_e( '— Select Organizer —', 'wp-events-calendar' ); ?></option>
                    <?php foreach ( $all_organizers as $org ) : ?>
                        <option value="<?php echo esc_attr( $org->id ); ?>"><?php echo esc_html( $org->name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="wpec-add-organizer" class="button"><?php esc_html_e( 'Add Organizer', 'wp-events-calendar' ); ?></button>
            </div>

            <hr>
            <p><strong><?php esc_html_e( 'Create New Organizer', 'wp-events-calendar' ); ?></strong></p>
            <table class="wpec-meta-table">
                <tr>
                    <th><label for="wpec_new_org_name"><?php esc_html_e( 'Name', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" id="wpec_new_org_name" class="widefat wpec-new-org-field" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_new_org_email"><?php esc_html_e( 'Email', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="email" id="wpec_new_org_email" class="widefat wpec-new-org-field" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_new_org_phone"><?php esc_html_e( 'Phone', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" id="wpec_new_org_phone" class="widefat wpec-new-org-field" /></td>
                </tr>
                <tr>
                    <th><label for="wpec_new_org_website"><?php esc_html_e( 'Website', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="url" id="wpec_new_org_website" class="widefat wpec-new-org-field" /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="button" id="wpec-create-organizer" class="button button-secondary"><?php esc_html_e( 'Create & Add Organizer', 'wp-events-calendar' ); ?></button>
                        <span id="wpec-org-status"></span>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Cost & Booking
    // -------------------------------------------------------------------------
    public static function render_cost_booking( \WP_Post $post ): void {
        $cost             = get_post_meta( $post->ID, '_wpec_cost', true );
        $cost_desc        = get_post_meta( $post->ID, '_wpec_cost_description', true );
        $currency         = get_post_meta( $post->ID, '_wpec_currency', true ) ?: get_option( 'wpec_currency', 'USD' );
        $booking_enabled  = get_post_meta( $post->ID, '_wpec_booking_enabled', true );
        $tickets_capacity = get_post_meta( $post->ID, '_wpec_tickets_capacity', true );
        $payment_method   = get_post_meta( $post->ID, '_wpec_payment_method', true );

        $currencies = WPEC_Helpers::get_currencies();
        $global_booking  = get_option( 'wpec_booking_enabled' );
        $global_payment  = get_option( 'wpec_payment_enabled' );
        ?>
        <div class="wpec-meta-section">
            <table class="wpec-meta-table">
                <tr>
                    <th><label for="wpec_cost"><?php esc_html_e( 'Event Cost (Optional)', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <input type="text" name="wpec_cost" id="wpec_cost" value="<?php echo esc_attr( $cost ); ?>" class="small-text" placeholder="0.00" />
                        <select name="wpec_currency" id="wpec_currency">
                            <?php foreach ( $currencies as $code => $label ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $currency, $code ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="wpec_cost_description"><?php esc_html_e( 'Cost Description', 'wp-events-calendar' ); ?></label></th>
                    <td><input type="text" name="wpec_cost_description" id="wpec_cost_description" value="<?php echo esc_attr( $cost_desc ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Free for members', 'wp-events-calendar' ); ?>" /></td>
                </tr>
                <?php if ( $global_booking ) : ?>
                <tr>
                    <th><label><?php esc_html_e( 'Booking', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_booking_enabled" id="wpec_booking_enabled" value="1" <?php checked( $booking_enabled, '1' ); ?> />
                            <?php esc_html_e( 'Enable booking for this event', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr class="wpec-booking-fields" <?php echo $booking_enabled ? '' : 'style="display:none"'; ?>>
                    <th><label for="wpec_tickets_capacity"><?php esc_html_e( 'Ticket Capacity', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <input type="number" name="wpec_tickets_capacity" id="wpec_tickets_capacity" value="<?php echo esc_attr( $tickets_capacity ); ?>" min="0" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Leave empty or 0 for unlimited.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <?php if ( $global_payment ) : ?>
                <tr class="wpec-booking-fields" <?php echo $booking_enabled ? '' : 'style="display:none"'; ?>>
                    <th><label><?php esc_html_e( 'Payment Gateway', 'wp-events-calendar' ); ?></label></th>
                    <td>
                        <?php
                        $gateways = WPEC_Payment::get_enabled_gateways();
                        foreach ( $gateways as $gw_id => $gw_label ) : ?>
                            <label>
                                <input type="radio" name="wpec_payment_method" value="<?php echo esc_attr( $gw_id ); ?>" <?php checked( $payment_method, $gw_id ); ?> />
                                <?php echo esc_html( $gw_label ); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Website & Links
    // -------------------------------------------------------------------------
    public static function render_links( \WP_Post $post ): void {
        $website = get_post_meta( $post->ID, '_wpec_website', true );
        $phone   = get_post_meta( $post->ID, '_wpec_phone', true );
        ?>
        <div class="wpec-meta-section">
            <p>
                <label for="wpec_website"><?php esc_html_e( 'Event Website', 'wp-events-calendar' ); ?></label>
                <input type="url" name="wpec_website" id="wpec_website" value="<?php echo esc_url( $website ); ?>" class="widefat" />
            </p>
            <p>
                <label for="wpec_phone"><?php esc_html_e( 'Phone', 'wp-events-calendar' ); ?></label>
                <input type="text" name="wpec_phone" id="wpec_phone" value="<?php echo esc_attr( $phone ); ?>" class="widefat" />
            </p>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Google Calendar
    // -------------------------------------------------------------------------
    public static function render_google_calendar( \WP_Post $post ): void {
        $gcal_id   = get_post_meta( $post->ID, '_wpec_gcal_event_id', true );
        $sync      = get_post_meta( $post->ID, '_wpec_gcal_sync', true );
        ?>
        <div class="wpec-meta-section">
            <?php if ( $gcal_id ) : ?>
                <p><?php esc_html_e( 'Synced with Google Calendar', 'wp-events-calendar' ); ?> ✓</p>
                <p><small><?php echo esc_html( $gcal_id ); ?></small></p>
            <?php endif; ?>
            <label>
                <input type="checkbox" name="wpec_gcal_sync" value="1" <?php checked( $sync, '1' ); ?> />
                <?php esc_html_e( 'Sync to Google Calendar', 'wp-events-calendar' ); ?>
            </label>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Save meta
    // -------------------------------------------------------------------------
    public static function save_meta( int $post_id, \WP_Post $post ): void {
        if ( ! isset( $_POST['wpec_event_meta_nonce'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpec_event_meta_nonce'] ) ), 'wpec_save_event_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Simple scalar fields
        $scalar_fields = [
            '_wpec_start_date'        => 'sanitize_text_field',
            '_wpec_start_time'        => 'sanitize_text_field',
            '_wpec_end_date'          => 'sanitize_text_field',
            '_wpec_end_time'          => 'sanitize_text_field',
            '_wpec_recurrence'        => 'sanitize_text_field',
            '_wpec_venue_name'        => 'sanitize_text_field',
            '_wpec_address'           => 'sanitize_text_field',
            '_wpec_city'              => 'sanitize_text_field',
            '_wpec_state'             => 'sanitize_text_field',
            '_wpec_country'           => 'sanitize_text_field',
            '_wpec_map_link'          => 'esc_url_raw',
            '_wpec_cost'              => 'sanitize_text_field',
            '_wpec_cost_description'  => 'sanitize_text_field',
            '_wpec_currency'          => 'sanitize_text_field',
            '_wpec_tickets_capacity'  => 'absint',
            '_wpec_payment_method'    => 'sanitize_text_field',
            '_wpec_website'           => 'esc_url_raw',
            '_wpec_phone'             => 'sanitize_text_field',
        ];

        // FIX BUG #3: Only delete a meta key if the field was actually rendered (not hidden
        // because global booking/payment is disabled). Fields not present in $_POST when their
        // parent section is disabled should be LEFT ALONE to avoid data loss.
        $booking_fields_rendered = (bool) get_option( 'wpec_booking_enabled' );
        $payment_fields_rendered = (bool) get_option( 'wpec_payment_enabled' );
        $protected_when_hidden   = [
            '_wpec_tickets_capacity',
            '_wpec_payment_method',
        ];

        foreach ( $scalar_fields as $meta_key => $sanitizer ) {
            $form_key = str_replace( '_wpec_', 'wpec_', $meta_key );
            if ( isset( $_POST[ $form_key ] ) ) {
                update_post_meta( $post_id, $meta_key, $sanitizer( wp_unslash( $_POST[ $form_key ] ) ) );
            } elseif ( ! in_array( $meta_key, $protected_when_hidden, true ) ) {
                // Only delete non-protected fields that are truly absent
                delete_post_meta( $post_id, $meta_key );
            }
            // Protected fields not in POST (because their section is hidden) are left unchanged
        }

        // Checkboxes
        update_post_meta( $post_id, '_wpec_all_day', isset( $_POST['wpec_all_day'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_wpec_show_map', isset( $_POST['wpec_show_map'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_wpec_booking_enabled', isset( $_POST['wpec_booking_enabled'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_wpec_gcal_sync', isset( $_POST['wpec_gcal_sync'] ) ? '1' : '0' );

        // Organizers
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'wpec_event_organizers', [ 'event_id' => $post_id ], [ '%d' ] );
        if ( isset( $_POST['wpec_organizers'] ) && is_array( $_POST['wpec_organizers'] ) ) {
            foreach ( array_map( 'absint', $_POST['wpec_organizers'] ) as $org_id ) {
                if ( $org_id ) {
                    $wpdb->replace( $wpdb->prefix . 'wpec_event_organizers', [
                        'event_id'     => $post_id,
                        'organizer_id' => $org_id,
                    ], [ '%d', '%d' ] );
                }
            }
        }

        // Google Calendar sync
        if ( isset( $_POST['wpec_gcal_sync'] ) ) {
            WPEC_Google_Calendar::sync_event( $post_id );
        }

        // Clear cache
        WPEC_Cache::clear_event_cache( $post_id );
    }

    private static function render_country_select( string $selected = '' ): void {
        $countries = WPEC_Helpers::get_countries();
        echo '<select name="wpec_country" id="wpec_country" class="widefat">';
        echo '<option value="">' . esc_html__( '— Select Country —', 'wp-events-calendar' ) . '</option>';
        foreach ( $countries as $code => $name ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $code ),
                selected( $selected, $code, false ),
                esc_html( $name )
            );
        }
        echo '</select>';
    }
}
