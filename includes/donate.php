<?php

/**
 * The admin-facing functionality of the plugin.
 *
 * @package    UPI QR Code Payment for WooCommerce
 * @subpackage Includes
 * @author     Sayan Datta
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */

add_action( 'admin_notices', 'upiwc_donate_admin_notice' );
add_action( 'admin_init', 'upiwc_dismiss_donate_admin_notice' );

function upiwc_donate_admin_notice() {
    // Show notice after 360 hours (15 days) from installed time.
    if ( upiwc_plugin_installed_time_donate() > strtotime( '-360 hours' )
        || '1' === get_option( 'upiwc_plugin_dismiss_donate_notice' )
        || ! current_user_can( 'manage_options' )
        || apply_filters( 'upiwc_plugin_hide_sticky_donate_notice', false ) ) {
        return;
    }

    $dismiss = wp_nonce_url( add_query_arg( 'upiwc_donate_notice_action', 'dismiss_donate_true' ), 'upiwc_dismiss_donate_true' ); 
    $no_thanks = wp_nonce_url( add_query_arg( 'upiwc_donate_notice_action', 'no_thanks_donate_true' ), 'upiwc_no_thanks_donate_true' ); ?>
    
    <div class="notice notice-success">
        <p><?php _e( 'Hey, I noticed you\'ve been using UPI QR Code Payment for WooCommerce for more than 2 week – that’s awesome! If you like UPI QR Code Payment for WooCommerce and you are satisfied with the plugin, isn’t that worth a coffee or two? Please consider donating. Donations help me to continue support and development of this free plugin! Thank you very much!', 'upi-qr-code-payment-for-woocommerce' ); ?></p>
        <p><a href="https://www.paypal.me/iamsayan" target="_blank" class="button button-secondary"><?php _e( 'Donate Now', 'upi-qr-code-payment-for-woocommerce' ); ?></a>&nbsp;
        <a href="<?php echo $dismiss; ?>" class="already-did"><strong><?php _e( 'I already donated', 'upi-qr-code-payment-for-woocommerce' ); ?></strong></a>&nbsp;<strong>|</strong>
        <a href="<?php echo $no_thanks; ?>" class="later"><strong><?php _e( 'Nope&#44; maybe later', 'upi-qr-code-payment-for-woocommerce' ); ?></strong></a></p>
    </div>
<?php
}

function upiwc_dismiss_donate_admin_notice() {
    if( get_option( 'upiwc_plugin_no_thanks_donate_notice' ) === '1' ) {
        if ( get_option( 'upiwc_plugin_dismissed_time_donate' ) > strtotime( '-360 hours' ) ) {
            return;
        }
        delete_option( 'upiwc_plugin_dismiss_donate_notice' );
        delete_option( 'upiwc_plugin_no_thanks_donate_notice' );
    }

    if ( ! isset( $_GET['upiwc_donate_notice_action'] ) ) {
        return;
    }

    if ( 'dismiss_donate_true' === $_GET['upiwc_donate_notice_action'] ) {
        check_admin_referer( 'upiwc_dismiss_donate_true' );
        update_option( 'upiwc_plugin_dismiss_donate_notice', '1' );
    }

    if ( 'no_thanks_donate_true' === $_GET['upiwc_donate_notice_action'] ) {
        check_admin_referer( 'upiwc_no_thanks_donate_true' );
        update_option( 'upiwc_plugin_no_thanks_donate_notice', '1' );
        update_option( 'upiwc_plugin_dismiss_donate_notice', '1' );
        update_option( 'upiwc_plugin_dismissed_time_donate', time() );
    }

    wp_redirect( remove_query_arg( 'upiwc_donate_notice_action' ) );
    exit;
}

function upiwc_plugin_installed_time_donate() {
    $installed_time = get_option( 'upiwc_plugin_installed_time_donate' );
    if ( ! $installed_time ) {
        $installed_time = time();
        update_option( 'upiwc_plugin_installed_time_donate', $installed_time );
    }
    return $installed_time;
}