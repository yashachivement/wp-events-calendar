<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpec-admin-wrap">
    <h1><?php esc_html_e( 'Events Calendar — Settings', 'wp-events-calendar' ); ?></h1>

    <nav class="nav-tab-wrapper wpec-tabs">
        <?php
        $tabs = [
            'general'  => __( 'General', 'wp-events-calendar' ),
            'display'  => __( 'Display', 'wp-events-calendar' ),
            'datetime' => __( 'Date & Time', 'wp-events-calendar' ),
            'currency' => __( 'Currency', 'wp-events-calendar' ),
            'maps'     => __( 'Maps', 'wp-events-calendar' ),
            'booking'  => __( 'Booking & Payment', 'wp-events-calendar' ),
            'google'   => __( 'Google Calendar', 'wp-events-calendar' ),
            'design'   => __( 'Theme & Design', 'wp-events-calendar' ),
            'advanced' => __( 'Advanced', 'wp-events-calendar' ),
        ];
        foreach ( $tabs as $slug => $label ) :
            $class = ( $tab === $slug ) ? 'nav-tab nav-tab-active' : 'nav-tab';
        ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpec-settings&tab=' . $slug ) ); ?>" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field( 'wpec_settings_save' ); ?>
        <input type="hidden" name="wpec_save_settings" value="1" />
        <input type="hidden" name="wpec_current_tab" value="<?php echo esc_attr( $tab ); ?>" />

        <div class="wpec-settings-body">

        <?php if ( $tab === 'general' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Events Base Slug', 'wp-events-calendar' ); ?></th>
                    <td>
                        <input type="text" name="wpec_event_slug" value="<?php echo esc_attr( $settings['event_slug'] ); ?>" class="regular-text" placeholder="event" />
                        <p class="description"><?php esc_html_e( 'The URL slug used for individual events (e.g., example.com/event/your-event-name). Default is "event".', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Main Events Page', 'wp-events-calendar' ); ?></th>
                    <td>
                        <?php
                        wp_dropdown_pages( [
                            'name'              => 'wpec_events_page_id',
                            'selected'          => $settings['events_page_id'],
                            'show_option_none'  => __( '— Select a Page —', 'wp-events-calendar' ),
                            'option_none_value' => '0',
                        ] );
                        ?>
                        <?php if ( $settings['events_page_id'] && get_permalink( $settings['events_page_id'] ) ) : ?>
                            <a href="<?php echo esc_url( get_permalink( $settings['events_page_id'] ) ); ?>" target="_blank" class="button button-secondary" style="margin-left:8px;"><?php esc_html_e( 'View Page ↗', 'wp-events-calendar' ); ?></a>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Select the primary page where your events calendar is placed.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Past Events', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_past_events" value="1" <?php checked( $settings['show_past_events'] ); ?> />
                            <?php esc_html_e( 'Show past events in calendar and archives', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Comments', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_enable_comments" value="1" <?php checked( $settings['enable_comments'] ); ?> />
                            <?php esc_html_e( 'Allow comments on event pages', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Shortcode Quick Helper', 'wp-events-calendar' ); ?></th>
                    <td>
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px 16px; max-width:600px;">
                            <p style="margin:0 0 8px 0;"><strong><?php esc_html_e( 'Embed Calendar:', 'wp-events-calendar' ); ?></strong> <code>[wpec_calendar]</code></p>
                            <p style="margin:0 0 8px 0;"><strong><?php esc_html_e( 'Upcoming Events List:', 'wp-events-calendar' ); ?></strong> <code>[wpec_events_list limit="5"]</code></p>
                            <p style="margin:0; font-size:12px; color:#6b7280;"><?php esc_html_e( 'Paste these shortcodes on any page, post, or widget area. Use the Shortcode Generator in the sidebar for more options.', 'wp-events-calendar' ); ?></p>
                        </div>
                    </td>
                </tr>
            </table>

        <?php elseif ( $tab === 'display' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Calendar Template', 'wp-events-calendar' ); ?></th>
                    <td>
                        <div class="wpec-template-grid">
                            <?php foreach ( [ 'classic' => 'Classic', 'modern' => 'Modern', 'minimal' => 'Minimal' ] as $tpl => $tpl_label ) : ?>
                                <label class="wpec-template-card <?php echo $settings['calendar_template'] === $tpl ? 'active' : ''; ?>">
                                    <input type="radio" name="wpec_calendar_template" value="<?php echo esc_attr( $tpl ); ?>" <?php checked( $settings['calendar_template'], $tpl ); ?> />
                                    <div class="wpec-template-thumb wpec-tpl-<?php echo esc_attr( $tpl ); ?>"></div>
                                    <span><?php echo esc_html( $tpl_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php esc_html_e( 'Choose the visual layout template used when rendering the calendar.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Default Calendar View', 'wp-events-calendar' ); ?></th>
                    <td>
                        <select name="wpec_default_view">
                            <?php foreach ( [ 'month'=>'Month','week'=>'Week','day'=>'Day','list'=>'List','summary'=>'Summary','photo'=>'Photo' ] as $v => $l ) : ?>
                                <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $settings['default_view'], $v ); ?>><?php echo esc_html( $l ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'The view displayed initially when a visitor opens the calendar.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Events Per Page', 'wp-events-calendar' ); ?></th>
                    <td>
                        <input type="number" name="wpec_events_per_page" value="<?php echo esc_attr( $settings['events_per_page'] ); ?>" min="1" max="100" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Number of events shown per page in List, Summary, and Photo views.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><h3 style="margin-top:1.5rem;"><?php esc_html_e( 'Calendar Elements', 'wp-events-calendar' ); ?></h3></th>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'View Switcher', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_view_switcher" value="1" <?php checked( $settings['show_view_switcher'] ); ?> />
                            <?php esc_html_e( 'Display the view switcher buttons (Month, Week, Day, List, etc.) on the calendar toolbar', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Category Filter', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_cat_filter" value="1" <?php checked( $settings['show_cat_filter'] ); ?> />
                            <?php esc_html_e( 'Display the category dropdown filter on the calendar toolbar', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Event Venue', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_venue" value="1" <?php checked( $settings['show_venue'] ); ?> />
                            <?php esc_html_e( 'Display venue information on calendar cards and list previews', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Event Cost / Badge', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_cost" value="1" <?php checked( $settings['show_cost'] ); ?> />
                            <?php esc_html_e( 'Display price or Free badge on calendar cards and list previews', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Add to Calendar', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpec_show_add_to_calendar" value="1" <?php checked( $settings['show_add_to_calendar'] ); ?> />
                            <?php esc_html_e( 'Display "Google Calendar" and "iCal / Outlook" export buttons on single event pages', 'wp-events-calendar' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

        <?php elseif ( $tab === 'datetime' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Date Format', 'wp-events-calendar' ); ?></th>
                    <td>
                        <?php
                        $date_formats = [ 'F j, Y' => 'F j, Y', 'Y-m-d' => 'Y-m-d', 'm/d/Y' => 'm/d/Y', 'd/m/Y' => 'd/m/Y', 'd.m.Y' => 'd.m.Y' ];
                        foreach ( $date_formats as $fmt => $preview ) : ?>
                            <label>
                                <input type="radio" name="wpec_date_format" value="<?php echo esc_attr( $fmt ); ?>" <?php checked( $settings['date_format'], $fmt ); ?> />
                                <?php echo esc_html( date_i18n( $preview ) ); ?> <code><?php echo esc_html( $fmt ); ?></code>
                            </label><br>
                        <?php endforeach; ?>
                        <label>
                            <input type="radio" name="wpec_date_format" value="custom" id="wpec_date_format_custom_radio" />
                            <?php esc_html_e( 'Custom:', 'wp-events-calendar' ); ?>
                            <input type="text" name="wpec_date_format_custom" value="" class="small-text" />
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Time Format', 'wp-events-calendar' ); ?></th>
                    <td>
                        <?php foreach ( [ 'g:i a' => '12-hour (1:30 pm)', 'H:i' => '24-hour (13:30)' ] as $fmt => $label ) : ?>
                            <label>
                                <input type="radio" name="wpec_time_format" value="<?php echo esc_attr( $fmt ); ?>" <?php checked( $settings['time_format'], $fmt ); ?> />
                                <?php echo esc_html( $label ); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Timezone', 'wp-events-calendar' ); ?></th>
                    <td>
                        <select name="wpec_timezone">
                            <?php echo wp_timezone_choice( $settings['timezone'] ); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Week Starts On', 'wp-events-calendar' ); ?></th>
                    <td>
                        <select name="wpec_week_starts_on">
                            <?php $days = [ 0=>'Sunday',1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday' ];
                            foreach ( $days as $num => $name ) : ?>
                                <option value="<?php echo esc_attr( $num ); ?>" <?php selected( $settings['week_starts_on'], $num ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

        <?php elseif ( $tab === 'currency' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Currency', 'wp-events-calendar' ); ?></th>
                    <td>
                        <select name="wpec_currency">
                            <?php foreach ( WPEC_Helpers::get_currencies() as $code => $label ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['currency'], $code ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Currency Symbol Position', 'wp-events-calendar' ); ?></th>
                    <td>
                        <label><input type="radio" name="wpec_currency_position" value="before" <?php checked( $settings['currency_position'], 'before' ); ?> /> <?php esc_html_e( 'Before amount ($99)', 'wp-events-calendar' ); ?></label><br>
                        <label><input type="radio" name="wpec_currency_position" value="after"  <?php checked( $settings['currency_position'], 'after' ); ?> /> <?php esc_html_e( 'After amount (99$)', 'wp-events-calendar' ); ?></label>
                    </td>
                </tr>
            </table>

        <?php elseif ( $tab === 'maps' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Default Map Provider', 'wp-events-calendar' ); ?></th>
                    <td>
                        <select name="wpec_default_map_provider">
                            <option value="google"     <?php selected( $settings['default_map_provider'], 'google' ); ?>><?php esc_html_e( 'Google Maps', 'wp-events-calendar' ); ?></option>
                            <option value="openstreet" <?php selected( $settings['default_map_provider'], 'openstreet' ); ?>><?php esc_html_e( 'OpenStreetMap', 'wp-events-calendar' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Google Maps API Key', 'wp-events-calendar' ); ?></th>
                    <td>
                        <input type="text" name="wpec_google_maps_api_key" value="<?php echo esc_attr( $settings['google_maps_api_key'] ); ?>" class="regular-text" />
                        <p class="description"><?php printf( __( 'Get your API key from <a href="%s" target="_blank">Google Cloud Console</a>.', 'wp-events-calendar' ), 'https://console.cloud.google.com/' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Show Map by Default', 'wp-events-calendar' ); ?></th>
                    <td><label><input type="checkbox" name="wpec_show_map_by_default" value="1" <?php checked( $settings['show_map_by_default'] ); ?> /> <?php esc_html_e( 'Show map on all event pages by default', 'wp-events-calendar' ); ?></label></td>
                </tr>
            </table>

        <?php elseif ( $tab === 'booking' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enable Booking', 'wp-events-calendar' ); ?></th>
                    <td><label><input type="checkbox" name="wpec_booking_enabled" value="1" <?php checked( $settings['booking_enabled'] ); ?> /> <?php esc_html_e( 'Allow attendees to book tickets', 'wp-events-calendar' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Enable Paid Tickets', 'wp-events-calendar' ); ?></th>
                    <td><label><input type="checkbox" name="wpec_payment_enabled" value="1" <?php checked( $settings['payment_enabled'] ); ?> /> <?php esc_html_e( 'Enable payment gateways', 'wp-events-calendar' ); ?></label></td>
                </tr>
                <tr><th colspan="2"><h3><?php esc_html_e( 'PayPal', 'wp-events-calendar' ); ?></h3></th></tr>
                <tr>
                    <th><?php esc_html_e( 'Enable PayPal', 'wp-events-calendar' ); ?></th>
                    <td><label><input type="checkbox" name="wpec_paypal_enabled" value="1" <?php checked( $settings['paypal_enabled'] ); ?> /> <?php esc_html_e( 'Accept PayPal payments', 'wp-events-calendar' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'PayPal Email', 'wp-events-calendar' ); ?></th>
                    <td><input type="email" name="wpec_paypal_email" value="<?php echo esc_attr( get_option( 'wpec_paypal_email' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'PayPal Sandbox', 'wp-events-calendar' ); ?></th>
                    <td><label><input type="checkbox" name="wpec_paypal_sandbox" value="1" <?php checked( get_option( 'wpec_paypal_sandbox', true ) ); ?> /> <?php esc_html_e( 'Use sandbox (test) mode', 'wp-events-calendar' ); ?></label></td>
                </tr>
                <tr><th colspan="2"><h3><?php esc_html_e( 'Stripe', 'wp-events-calendar' ); ?></h3></th></tr>
                <tr>
                    <th><?php esc_html_e( 'Enable Stripe', 'wp-events-calendar' ); ?></th>
                    <td><label><input type="checkbox" name="wpec_stripe_enabled" value="1" <?php checked( $settings['stripe_enabled'] ); ?> /> <?php esc_html_e( 'Accept card payments via Stripe', 'wp-events-calendar' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Stripe Publishable Key', 'wp-events-calendar' ); ?></th>
                    <td><input type="text" name="wpec_stripe_public_key" value="<?php echo esc_attr( get_option( 'wpec_stripe_public_key' ) ); ?>" class="regular-text" placeholder="pk_..." /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Stripe Secret Key', 'wp-events-calendar' ); ?></th>
                    <td><input type="password" name="wpec_stripe_secret_key" value="<?php echo esc_attr( get_option( 'wpec_stripe_secret_key' ) ); ?>" class="regular-text" placeholder="sk_..." /></td>
                </tr>
            </table>

        <?php elseif ( $tab === 'google' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Connection Status', 'wp-events-calendar' ); ?></th>
                    <td>
                        <?php if ( WPEC_Google_Calendar::is_connected() ) : ?>
                            <span class="wpec-badge wpec-badge-success">✓ <?php esc_html_e( 'Connected to Google Calendar', 'wp-events-calendar' ); ?></span>
                            <button type="button" id="wpec-gcal-disconnect" class="button button-secondary" style="margin-left:1rem;"><?php esc_html_e( 'Disconnect', 'wp-events-calendar' ); ?></button>
                        <?php else : ?>
                            <span class="wpec-badge wpec-badge-warning"><?php esc_html_e( 'Not connected', 'wp-events-calendar' ); ?></span>
                            <a href="<?php echo esc_url( WPEC_Google_Calendar::get_auth_url() ); ?>" class="button button-primary" style="margin-left:1rem;"><?php esc_html_e( 'Connect Google Calendar', 'wp-events-calendar' ); ?></a>
                        <?php endif; ?>
                        <?php if ( isset( $_GET['gcal_connected'] ) ) echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Google Calendar connected!', 'wp-events-calendar' ) . '</p></div>'; ?>
                        <?php if ( isset( $_GET['gcal_error'] ) ) echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Google Calendar connection failed.', 'wp-events-calendar' ) . '</p></div>'; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Google Client ID', 'wp-events-calendar' ); ?></th>
                    <td><input type="text" name="wpec_google_client_id" value="<?php echo esc_attr( get_option( 'wpec_google_client_id' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Google Client Secret', 'wp-events-calendar' ); ?></th>
                    <td><input type="password" name="wpec_google_client_secret" value="<?php echo esc_attr( get_option( 'wpec_google_client_secret' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Calendar ID', 'wp-events-calendar' ); ?></th>
                    <td>
                        <input type="text" name="wpec_google_calendar_id" value="<?php echo esc_attr( get_option( 'wpec_google_calendar_id', 'primary' ) ); ?>" class="regular-text" placeholder="primary" />
                        <p class="description"><?php esc_html_e( 'Use "primary" for your main calendar, or enter a specific calendar ID.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Redirect URI', 'wp-events-calendar' ); ?></th>
                    <td>
                        <code><?php echo esc_html( admin_url( 'admin.php?action=wpec_gcal_callback' ) ); ?></code>
                        <p class="description"><?php esc_html_e( 'Add this as an authorised redirect URI in your Google Cloud Console OAuth2 credentials.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ( $tab === 'design' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Primary Color', 'wp-events-calendar' ); ?></th>
                    <td><input type="color" name="wpec_theme_primary_color" value="<?php echo esc_attr( $settings['theme_primary_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Secondary Color', 'wp-events-calendar' ); ?></th>
                    <td><input type="color" name="wpec_theme_secondary_color" value="<?php echo esc_attr( $settings['theme_secondary_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Text Color', 'wp-events-calendar' ); ?></th>
                    <td><input type="color" name="wpec_theme_text_color" value="<?php echo esc_attr( $settings['theme_text_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Background Color', 'wp-events-calendar' ); ?></th>
                    <td><input type="color" name="wpec_theme_bg_color" value="<?php echo esc_attr( $settings['theme_bg_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Additional Content', 'wp-events-calendar' ); ?></th>
                    <td>
                        <?php wp_editor( $settings['additional_content'], 'wpec_additional_content', [ 'textarea_rows' => 5, 'media_buttons' => false ] ); ?>
                        <p class="description"><?php esc_html_e( 'Content appended below every event.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ( $tab === 'advanced' ) : ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Clear Cache', 'wp-events-calendar' ); ?></th>
                    <td>
                        <button type="button" id="wpec-clear-cache" class="button button-secondary"><?php esc_html_e( 'Clear All Cache', 'wp-events-calendar' ); ?></button>
                        <div id="wpec-cache-msg" style="display:none;" class="notice notice-success inline"><p></p></div>
                        <p class="description"><?php esc_html_e( 'Flush the events calendar cache to see recent changes on the frontend.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'iCal Feed URL', 'wp-events-calendar' ); ?></th>
                    <td>
                        <code><?php echo esc_url( home_url( 'events.ics' ) ); ?></code>
                        <p class="description"><?php esc_html_e( 'Public iCal feed. Subscribe in any calendar app.', 'wp-events-calendar' ); ?></p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        </div><!-- .wpec-settings-body -->

        <?php if ( $tab !== 'advanced' ) : ?>
            <?php submit_button( __( 'Save Settings', 'wp-events-calendar' ) ); ?>
        <?php endif; ?>
    </form>
</div>
