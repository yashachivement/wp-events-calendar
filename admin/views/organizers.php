<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpec-admin-wrap">
    <h1><?php esc_html_e( 'Organizers', 'wp-events-calendar' ); ?></h1>

    <div class="wpec-two-col">
        <!-- Form: Add / Edit -->
        <div class="wpec-col wpec-col-left">
            <div class="wpec-card">
                <h2><?php echo $editing ? esc_html__( 'Edit Organizer', 'wp-events-calendar' ) : esc_html__( 'Add New Organizer', 'wp-events-calendar' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'wpec_organizer_save' ); ?>
                    <input type="hidden" name="wpec_organizer_action" value="save" />
                    <input type="hidden" name="org_id" value="<?php echo esc_attr( $editing ? $editing->id : 0 ); ?>" />

                    <table class="form-table">
                        <tr>
                            <th><label for="org_name"><?php esc_html_e( 'Name', 'wp-events-calendar' ); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="org_name" name="org_name" value="<?php echo esc_attr( $editing ? $editing->name : '' ); ?>" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th><label for="org_email"><?php esc_html_e( 'Email', 'wp-events-calendar' ); ?></label></th>
                            <td><input type="email" id="org_email" name="org_email" value="<?php echo esc_attr( $editing ? $editing->email : '' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="org_phone"><?php esc_html_e( 'Phone', 'wp-events-calendar' ); ?></label></th>
                            <td><input type="text" id="org_phone" name="org_phone" value="<?php echo esc_attr( $editing ? $editing->phone : '' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="org_website"><?php esc_html_e( 'Website', 'wp-events-calendar' ); ?></label></th>
                            <td><input type="url" id="org_website" name="org_website" value="<?php echo esc_url( $editing ? $editing->website : '' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="org_description"><?php esc_html_e( 'Description', 'wp-events-calendar' ); ?></label></th>
                            <td><textarea id="org_description" name="org_description" class="large-text" rows="4"><?php echo esc_textarea( $editing ? $editing->description : '' ); ?></textarea></td>
                        </tr>
                    </table>

                    <?php submit_button( $editing ? __( 'Update Organizer', 'wp-events-calendar' ) : __( 'Add Organizer', 'wp-events-calendar' ) ); ?>
                    <?php if ( $editing ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpec-organizers' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'wp-events-calendar' ); ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="wpec-col wpec-col-right">
            <div class="wpec-card">
                <h2><?php esc_html_e( 'All Organizers', 'wp-events-calendar' ); ?></h2>
                <?php if ( empty( $organizers ) ) : ?>
                    <p><?php esc_html_e( 'No organizers yet.', 'wp-events-calendar' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Name', 'wp-events-calendar' ); ?></th>
                                <th><?php esc_html_e( 'Email', 'wp-events-calendar' ); ?></th>
                                <th><?php esc_html_e( 'Phone', 'wp-events-calendar' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'wp-events-calendar' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $organizers as $org ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $org->name ); ?></strong><?php if ( $org->website ) echo ' — <a href="' . esc_url( $org->website ) . '" target="_blank">' . esc_html__( 'Website', 'wp-events-calendar' ) . '</a>'; ?></td>
                                    <td><?php echo $org->email ? '<a href="mailto:' . esc_attr( $org->email ) . '">' . esc_html( $org->email ) . '</a>' : '—'; ?></td>
                                    <td><?php echo esc_html( $org->phone ?: '—' ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpec-organizers&edit=' . $org->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-events-calendar' ); ?></a>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this organizer?', 'wp-events-calendar' ); ?>');">
                                            <?php wp_nonce_field( 'wpec_organizer_save' ); ?>
                                            <input type="hidden" name="wpec_organizer_action" value="delete" />
                                            <input type="hidden" name="org_id" value="<?php echo esc_attr( $org->id ); ?>" />
                                            <button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'wp-events-calendar' ); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
