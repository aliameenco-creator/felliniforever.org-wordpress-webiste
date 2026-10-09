<?php
/**
 * Contact form recreated from the original Strikingly email form (fields, order, labels, submit
 * label and thank-you text in parts/contact-form.json). Submissions are saved as private
 * "Enquiries" in wp-admin and emailed to the configured address. No third-party service is used.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fellini_form_definition() {
    static $form = null;
    if ( null === $form ) {
        $file = get_theme_file_path( 'parts/contact-form.json' );
        $form = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return is_array( $form ) ? $form : array();
}

function fellini_contact_recipient() {
    $email = sanitize_email( (string) get_option( 'fellini_contact_recipient', '' ) );
    return is_email( $email ) ? $email : '';
}

add_action( 'init', function () {
    register_post_type( 'fellini_enquiry', array(
        'labels'          => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry', 'menu_name' => 'Enquiries', 'all_items' => 'All enquiries', 'edit_item' => 'Enquiry' ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false,
        'menu_icon'       => 'dashicons-email-alt',
        'menu_position'   => 26,
        'supports'        => array( 'title' ),
        'capability_type' => 'post',
        'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap'    => true,
    ) );
} );

/** Recipient address under Settings > General. */
add_action( 'admin_init', function () {
    register_setting( 'general', 'fellini_contact_recipient', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );
    add_settings_field( 'fellini_contact_recipient', 'FELLINI contact form recipient', function () {
        echo '<input type="email" class="regular-text" name="fellini_contact_recipient" value="' . esc_attr( get_option( 'fellini_contact_recipient', '' ) ) . '" placeholder="the configured enquiry address"><p class="description">Enquiries from the Contact page are emailed here and always saved under Enquiries. Set the destination before launch. Until configured, submissions are saved privately without email.</p>';
    }, 'general' );
} );

add_action( 'add_meta_boxes_fellini_enquiry', function () {
    add_meta_box( 'fellini_enquiry_fields', 'Submitted details', function ( $post ) {
        $values = (array) get_post_meta( $post->ID, '_fellini_fields', true );
        echo '<table class="widefat striped"><tbody>';
        foreach ( fellini_form_definition()['fields'] ?? array() as $field ) {
            echo '<tr><th style="width:180px">' . esc_html( $field['label'] ) . '</th><td style="white-space:pre-wrap">' . esc_html( $values[ $field['key'] ] ?? '' ) . '</td></tr>';
        }
        $mail = get_post_meta( $post->ID, '_fellini_mail', true );
        echo '<tr><th>Email notification</th><td>' . esc_html( $mail ? $mail : 'unknown' ) . '</td></tr><tr><th>Page</th><td>' . esc_html( get_post_meta( $post->ID, '_fellini_page', true ) ) . '</td></tr></tbody></table>';
    }, 'fellini_enquiry', 'normal', 'high' );
} );
add_filter( 'manage_fellini_enquiry_posts_columns', function ( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'Name', 'fellini_email' => 'Email', 'fellini_mail' => 'Email sent', 'date' => 'Received' );
} );
add_action( 'manage_fellini_enquiry_posts_custom_column', function ( $column, $id ) {
    $values = (array) get_post_meta( $id, '_fellini_fields', true );
    if ( 'fellini_email' === $column ) { echo esc_html( $values['email'] ?? '' ); }
    if ( 'fellini_mail' === $column ) { echo esc_html( get_post_meta( $id, '_fellini_mail', true ) ); }
}, 10, 2 );

function fellini_form_respond( $ok, $message, $errors = array(), $values = array() ) {
    if ( str_contains( (string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'application/json' ) ) {
        wp_send_json( array( 'ok' => $ok, 'message' => $message, 'errors' => $errors ), $ok ? 200 : 422 );
    }
    $back = wp_get_referer() ? wp_get_referer() : home_url( '/contact' );
    $back = remove_query_arg( array( 'fellini_form', 'fellini_token' ), $back );
    if ( $ok ) {
        wp_safe_redirect( add_query_arg( 'fellini_form', 'sent', $back ) . '#enquiry' );
        exit;
    }
    $token = wp_generate_password( 20, false );
    set_transient( 'fellini_form_' . $token, array( 'message' => $message, 'errors' => $errors, 'values' => $values ), 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( array( 'fellini_form' => 'error', 'fellini_token' => $token ), $back ) . '#enquiry' );
    exit;
}

function fellini_handle_contact() {
    $form = fellini_form_definition();
    $values = array();
    $errors = array();
    foreach ( $form['fields'] ?? array() as $field ) {
        $raw = isset( $_POST[ 'fellini_' . $field['key'] ] ) && is_scalar( $_POST[ 'fellini_' . $field['key'] ] ) ? wp_unslash( $_POST[ 'fellini_' . $field['key'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidationSanitization.InputNotSanitized -- Public form; sanitised below; see anti-spam checks.
        $value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
        $values[ $field['key'] ] = $value;
        if ( $field['required'] && '' === trim( $value ) ) { $errors[ $field['key'] ] = $field['label'] . ' is required.'; }
        elseif ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) { $errors[ $field['key'] ] = 'Please enter a valid email address.'; }
        elseif ( mb_strlen( $value ) > (int) $field['max'] ) { $errors[ $field['key'] ] = $field['label'] . ' is too long (maximum ' . (int) $field['max'] . ' characters).'; }
    }
    // Spam protection that survives page caching: honeypot, minimum time on page (JavaScript), per-visitor rate limit.
    if ( ! empty( $_POST['fellini_website'] ) ) { fellini_form_respond( true, $form['thanks'] ?? '' ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
    // Milliseconds the visitor spent on the page, measured in the browser (independent of clock differences).
    $elapsed = isset( $_POST['fellini_elapsed'] ) ? (int) $_POST['fellini_elapsed'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( $elapsed > 0 && $elapsed < 3000 ) {
        fellini_form_respond( false, 'Your message could not be verified. Please wait a moment and submit again, or email the configured enquiry address.', array(), $values );
    }
    $visitor = 'fellini_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
    $count = (int) get_transient( $visitor );
    if ( $count >= 5 ) { fellini_form_respond( false, 'Too many messages in a short time. Please try again later or email the configured enquiry address.', array(), $values ); }
    if ( $errors ) { fellini_form_respond( false, 'Please check the highlighted fields.', $errors, $values ); }
    set_transient( $visitor, $count + 1, 10 * MINUTE_IN_SECONDS );

    $page = esc_url_raw( (string) wp_get_referer() );
    $id = wp_insert_post( array( 'post_type' => 'fellini_enquiry', 'post_status' => 'private', 'post_title' => $values['name'] ?? 'Enquiry' ), true );
    if ( is_wp_error( $id ) ) { fellini_form_respond( false, 'Your message could not be saved. Please try again.', array(), $values ); }
    if ( ! is_wp_error( $id ) ) {
        update_post_meta( $id, '_fellini_fields', $values );
        update_post_meta( $id, '_fellini_page', $page );
    }
    $lines = array();
    foreach ( $form['fields'] as $field ) { $lines[] = $field['label'] . ': ' . ( '' === $values[ $field['key'] ] ? '-' : $values[ $field['key'] ] ); }
    $lines[] = '';
    $lines[] = 'Sent from: ' . $page;
    $headers = array( 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $values['name'] ?? '' ) . ' <' . $values['email'] . '>' );
    $sent = fellini_contact_recipient() && wp_mail( fellini_contact_recipient(), 'Fellini Forever website enquiry from ' . ( $values['name'] ?? '' ), implode( "\n", $lines ), $headers );
    if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_fellini_mail', $sent ? 'sent to ' . fellini_contact_recipient() : ( fellini_contact_recipient() ? 'FAILED - check site email settings' : 'saved; recipient not configured' ) ); }
    fellini_form_respond( true, $form['thanks'] ?? 'Thank you.' );
}
add_action( 'admin_post_nopriv_fellini_contact', 'fellini_handle_contact' );
add_action( 'admin_post_fellini_contact', 'fellini_handle_contact' );

function fellini_contact_form() {
    $form = fellini_form_definition();
    if ( ! $form ) { return; }
    $state = array( 'message' => '', 'errors' => array(), 'values' => array() );
    $status = isset( $_GET['fellini_form'] ) ? sanitize_key( wp_unslash( $_GET['fellini_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( 'error' === $status && isset( $_GET['fellini_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = get_transient( 'fellini_form_' . sanitize_key( wp_unslash( $_GET['fellini_token'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_array( $saved ) ) { $state = $saved; }
    }
    echo '<form class="fellini-form" id="enquiry" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate data-fellini-form>';
    echo '<div class="form-status" role="status" aria-live="polite">';
    if ( 'sent' === $status ) { echo '<p class="form-success">' . esc_html( $form['thanks'] ) . '</p>'; }
    elseif ( $state['message'] ) { echo '<p class="form-error">' . esc_html( $state['message'] ) . '</p>'; }
    echo '</div><input type="hidden" name="action" value="fellini_contact"><input type="hidden" name="fellini_elapsed" value="" data-elapsed>';
    echo '<div class="hp" aria-hidden="true"><label>Website <input type="text" name="fellini_website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">';
    foreach ( $form['fields'] as $field ) {
        $name = 'fellini_' . $field['key'];
        $value = $state['values'][ $field['key'] ] ?? '';
        $error = $state['errors'][ $field['key'] ] ?? '';
        $attrs = ' placeholder="' . esc_attr( $field['placeholder'] ?? '' ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" maxlength="' . (int) $field['max'] . '"' . ( $field['required'] ? ' required aria-required="true"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );
        $auto = array( 'name' => 'name', 'email' => 'email', 'phone' => 'tel' );
        if ( isset( $auto[ $field['key'] ] ) ) { $attrs .= ' autocomplete="' . esc_attr( $auto[ $field['key'] ] ) . '"'; }
        echo '<div class="field field-' . esc_attr( $field['key'] ) . ( 'textarea' === $field['type'] ? ' wide' : '' ) . '"><label for="' . esc_attr( $name ) . '">' . esc_html( $field['label'] ) . ( $field['required'] ? ' <span class="req" aria-hidden="true">*</span>' : '' ) . '</label>';
        if ( 'textarea' === $field['type'] ) { echo '<textarea rows="6"' . $attrs . '>' . esc_textarea( $value ) . '</textarea>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes escaped above.
        else { echo '<input type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $value ) . '"' . $attrs . '>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span class="field-error">' . esc_html( $error ) . '</span></div>';
    }
    echo '</div><div class="form-actions"><button class="button" type="submit">' . esc_html( strtoupper( $form['submit'] ) ) . ' ↗</button><span class="form-note">* required</span></div>';
    echo '</form>';
}
