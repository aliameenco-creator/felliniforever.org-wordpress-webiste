<?php
/** Original email-only newsletter form; owned private storage, no external subscription service. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'init', function () {
    register_post_type( 'fellini_signup', array( 'labels' => array( 'name' => 'Newsletter sign-ups', 'singular_name' => 'Newsletter sign-up' ), 'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'supports' => array( 'title' ), 'capabilities' => array( 'create_posts' => 'do_not_allow' ), 'map_meta_cap' => true ) );
} );
add_shortcode( 'fellini_newsletter', function () {
    $status = isset( $_GET['fellini_signup'] ) ? sanitize_key( wp_unslash( $_GET['fellini_signup'] ) ) : '';
    $message = 'sent' === $status ? 'Thanks for submitting!' : ( 'error' === $status ? 'Please enter a valid email address and try again.' : '' );
    return '<form class="newsletter-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="fellini_signup"><label for="signup_email">Enter your email here</label><div class="newsletter-fields"><input id="signup_email" type="email" name="email" required maxlength="250" autocomplete="email"><button class="button" type="submit">Sign Up ↗</button></div><div class="hp" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off" aria-label="Website"></div><p role="status">' . esc_html( $message ) . '</p></form>';
} );
function fellini_handle_signup() {
    $email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $ok = false;
    $rate = 'fellini_signup_rate_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
    if ( ! empty( $_POST['website'] ) ) { $ok = true; }
    elseif ( is_email( $email ) && strlen( $email ) <= 250 && (int) get_transient( $rate ) < 5 ) {
        set_transient( $rate, (int) get_transient( $rate ) + 1, 10 * MINUTE_IN_SECONDS );
        // Atomic email-key reservation prevents duplicates from concurrent requests.
        $key = 'fellini_signup_' . hash_hmac( 'sha256', strtolower( $email ), wp_salt() );
        if ( add_option( $key, 'pending', '', false ) ) {
            $id = wp_insert_post( array( 'post_type' => 'fellini_signup', 'post_status' => 'private', 'post_title' => $email ), true );
            if ( is_wp_error( $id ) ) { delete_option( $key ); }
            else {
                update_option( $key, $id, false );
                $recipient = fellini_contact_recipient();
                $sent = $recipient && wp_mail( $recipient, 'Fellini Forever newsletter sign-up', 'New sign-up: ' . $email );
                update_post_meta( $id, '_fellini_mail', $sent ? 'sent' : ( $recipient ? 'failed' : 'recipient not configured' ) );
                $ok = true;
            }
        } else { $ok = true; }
    }
    wp_safe_redirect( add_query_arg( 'fellini_signup', $ok ? 'sent' : 'error', home_url( '/' ) ) . '#signup_email' );
    exit;
}
add_action( 'admin_post_nopriv_fellini_signup', 'fellini_handle_signup' );
add_action( 'admin_post_fellini_signup', 'fellini_handle_signup' );
