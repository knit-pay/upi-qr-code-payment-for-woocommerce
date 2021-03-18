<?php
/**
 * Plugin Name: UPI QR Code Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/
 * Description: It enables a WooCommerce site to accept payments through UPI apps like BHIM, Google Pay, Paytm, PhonePe or any Banking UPI app. Avoid payment gateway charges.
 * Version: 1.2.0
 * Author: Sayan Datta
 * Author URI: https://www.sayandatta.in
 * License: GPLv3
 * Text Domain: upi-qr-code-payment-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.1
 * WC tested up to: 5.1
 * 
 * UPI QR Code Payment Gateway is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * UPI QR Code Payment Gateway is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with UPI QR Code Payment Gateway plugin. If not, see <http://www.gnu.org/licenses/>.
 * 
 * @category WooCommerce
 * @package  UPI QR Code Payment Gateway
 * @author   Sayan Datta <hello@sayandatta.in>
 * @license  http://www.gnu.org/licenses/ GNU General Public License
 * @link     https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$consts = array(
    'UPI_WOO_PLUGIN_VERSION'       => '1.2.0', // plugin version
    'UPI_WOO_PLUGIN_BASENAME'      => plugin_basename( __FILE__ ),
	'UPI_WOO_PLUGIN_DIR'           => plugin_dir_url( __FILE__ ),
	//'UPI_WOO_PLUGIN_ENABLE_DEBUG'  => true
);

foreach( $consts as $const => $value ) {
    if ( ! defined( $const ) ) {
        define( $const, $value );
    }
}

// Internationalization
add_action( 'plugins_loaded', 'upiwc_plugin_load_textdomain' );

/**
 * Load plugin textdomain.
 * 
 * @since 1.0.0
 */
function upiwc_plugin_load_textdomain() {
    load_plugin_textdomain( 'upi-qr-code-payment-for-woocommerce', false, dirname( UPI_WOO_PLUGIN_BASENAME ) . '/languages/' ); 
}

// register activation hook
register_activation_hook( __FILE__, 'upiwc_plugin_activation' );

function upiwc_plugin_activation() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    set_transient( 'upiwc-admin-notice-on-activation', true, 5 );
}

// register deactivation hook
register_deactivation_hook( __FILE__, 'upiwc_plugin_deactivation' );

function upiwc_plugin_deactivation() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    delete_option( 'upiwc_plugin_dismiss_rating_notice' );
    delete_option( 'upiwc_plugin_no_thanks_rating_notice' );
    delete_option( 'upiwc_plugin_installed_time' );
}

// plugin action links
add_filter( 'plugin_action_links_' . UPI_WOO_PLUGIN_BASENAME, 'upiwc_add_action_links', 10, 2 );

function upiwc_add_action_links( $links ) {
    $upiwclinks = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-upi' ) . '">' . __( 'Settings', 'upi-qr-code-payment-for-woocommerce' ) . '</a>',
    );
    return array_merge( $upiwclinks, $links );
}

// plugin row elements
add_filter( 'plugin_row_meta', 'upiwc_plugin_meta_links', 10, 2 );

function upiwc_plugin_meta_links( $links, $file ) {
    $plugin = UPI_WOO_PLUGIN_BASENAME;
    if ( $file == $plugin ) // only for this plugin
        return array_merge( $links, 
            array( '<a href="https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/" target="_blank">' . __( 'Support', 'upi-qr-code-payment-for-woocommerce' ) . '</a>' ),
            array( '<a href="https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/#faq" target="_blank">' . __( 'FAQ', 'upi-qr-code-payment-for-woocommerce' ) . '</a>' ),
            array( '<a href="https://www.paypal.me/iamsayan/" target="_blank">' . __( 'Donate', 'upi-qr-code-payment-for-woocommerce' ) . '</a>' )
        );
    return $links;
}

// add admin notices
add_action( 'admin_notices', 'upiwc_new_plugin_install_notice' );

function upiwc_new_plugin_install_notice() { 
    // Show a warning to sites running PHP < 5.6
    if( version_compare( PHP_VERSION, '5.6', '<' ) ) {
	    echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by UPI QR Code Payment Gateway plugin. Please contact your host and request that your version be upgraded to 5.6 or later.', 'upi-qr-code-payment-for-woocommerce' ) . '</p></div>';
    }

    // Check transient, if available display notice
    if( get_transient( 'upiwc-admin-notice-on-activation' ) ) { ?>
        <div class="notice notice-success">
            <p><strong><?php printf( __( 'Thanks for installing %1$s v%2$s plugin. Click <a href="%3$s">here</a> to configure plugin settings.', 'upi-qr-code-payment-for-woocommerce' ), 'UPI QR Code Payment Gateway', UPI_WOO_PLUGIN_VERSION, admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-upi' ) ); ?></strong></p>
        </div> <?php
        delete_transient( 'upiwc-admin-notice-on-activation' );
    }
}

require_once plugin_dir_path( __FILE__ ) . 'includes/payment.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/notice.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/donate.php';