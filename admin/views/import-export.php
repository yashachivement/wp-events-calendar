<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpec-admin-wrap">
    <h1><?php esc_html_e( 'Import / Export Events', 'wp-events-calendar' ); ?></h1>

    <div class="wpec-two-col">
        <!-- Export -->
        <div class="wpec-col">
            <div class="wpec-card">
                <h2><?php esc_html_e( 'Export Events', 'wp-events-calendar' ); ?></h2>
                <p><?php esc_html_e( 'Download all published events as a CSV or iCal file.', 'wp-events-calendar' ); ?></p>

                <div class="wpec-export-buttons">
                    <button type="button" id="wpec-export-csv" class="button button-primary button-large">
                        📊 <?php esc_html_e( 'Export as CSV', 'wp-events-calendar' ); ?>
                    </button>
                    <button type="button" id="wpec-export-ical" class="button button-secondary button-large">
                        📅 <?php esc_html_e( 'Export as iCal (.ics)', 'wp-events-calendar' ); ?>
                    </button>
                </div>

                <hr>
                <h3><?php esc_html_e( 'Public iCal Feed', 'wp-events-calendar' ); ?></h3>
                <p><?php esc_html_e( 'Anyone can subscribe to your events using this URL in Google Calendar, Apple Calendar, Outlook, etc.', 'wp-events-calendar' ); ?></p>
                <div class="wpec-ical-url">
                    <input type="text" value="<?php echo esc_url( home_url( 'events.ics' ) ); ?>" readonly class="regular-text" id="wpec-ical-url" />
                    <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('wpec-ical-url').value); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',2000);"><?php esc_html_e( 'Copy', 'wp-events-calendar' ); ?></button>
                </div>
            </div>
        </div>

        <!-- Import -->
        <div class="wpec-col">
            <div class="wpec-card">
                <h2><?php esc_html_e( 'Import Events', 'wp-events-calendar' ); ?></h2>

                <div class="wpec-import-section">
                    <h3><?php esc_html_e( 'Import from CSV', 'wp-events-calendar' ); ?></h3>
                    <p><?php esc_html_e( 'Upload a CSV file. Use the exported CSV as a template for the correct column format.', 'wp-events-calendar' ); ?></p>
                    <div class="wpec-upload-zone" id="wpec-csv-drop-zone">
                        <span>📂</span>
                        <p><?php esc_html_e( 'Drag & drop CSV file here, or click to browse', 'wp-events-calendar' ); ?></p>
                        <input type="file" id="wpec-csv-file" accept=".csv" style="display:none" />
                        <button type="button" class="button" onclick="document.getElementById('wpec-csv-file').click()"><?php esc_html_e( 'Choose CSV File', 'wp-events-calendar' ); ?></button>
                    </div>
                    <button type="button" id="wpec-import-csv" class="button button-primary" disabled><?php esc_html_e( 'Import CSV', 'wp-events-calendar' ); ?></button>
                </div>

                <hr>

                <div class="wpec-import-section">
                    <h3><?php esc_html_e( 'Import from iCal (.ics)', 'wp-events-calendar' ); ?></h3>
                    <p><?php esc_html_e( 'Import events from Google Calendar, Apple Calendar, or any iCal-compatible source.', 'wp-events-calendar' ); ?></p>
                    <div class="wpec-upload-zone" id="wpec-ical-drop-zone">
                        <span>📅</span>
                        <p><?php esc_html_e( 'Drag & drop .ics file here, or click to browse', 'wp-events-calendar' ); ?></p>
                        <input type="file" id="wpec-ical-file" accept=".ics" style="display:none" />
                        <button type="button" class="button" onclick="document.getElementById('wpec-ical-file').click()"><?php esc_html_e( 'Choose iCal File', 'wp-events-calendar' ); ?></button>
                    </div>
                    <button type="button" id="wpec-import-ical" class="button button-primary" disabled><?php esc_html_e( 'Import iCal', 'wp-events-calendar' ); ?></button>
                </div>

                <div id="wpec-import-result" style="display:none" class="notice notice-success"><p></p></div>
            </div>
        </div>
    </div>
</div>
