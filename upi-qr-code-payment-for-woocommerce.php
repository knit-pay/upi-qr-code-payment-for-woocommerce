<?php
/**
 * Plugin Name: UPI QR Code Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/
 * Description: It enables a WooCommerce site to accept payments through UPI apps like BHIM, Google Pay, Paytm, PhonePe or any Banking UPI app. Avoid payment gateway charges.
 * Version: 1.4.2
 * Author: Sayan Datta
 * Author URI: https://www.sayandatta.co.in
 * License: GPLv3
 * Text Domain: upi-qr-code-payment-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.1
 * WC tested up to: 8.6
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
 * @author   Sayan Datta <iamsayan@protonmail.com>
 * @license  http://www.gnu.org/licenses/ GNU General Public License
 * @link     https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/
 *
 */

// If this file is called directly, abort!!!
defined( 'ABSPATH' ) || exit;

/**
 * UPIWC class.
 *
 * @class Main class of the plugin.
 */
final class UPIWC {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.4.2';

    /**
     * Minimum version of WordPress required to run UPIWC.
     *
     * @var string
     */
    private $wordpress_version = '4.6';

    /**
     * Minimum version of PHP required to run UPIWC.
     *
     * @var string
     */
    private $php_version = '5.6';

    /**
     * Hold install error messages.
     *
     * @var bool
     */
    private $messages = [];

    /**
     * The single instance of the class.
     *
     * @var UPIWC
     */
    protected static $instance = null;

    /**
     * Retrieve main UPIWC instance.
     *
     * Ensure only one instance is loaded or can be loaded.
     *
     * @see upiwc()
     * @return UPIWC
     */
    public static function get() {
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof UPIWC ) ) {
            self::$instance = new UPIWC();
            self::$instance->setup();
        }

        return self::$instance;
    }

    /**
     * Instantiate the plugin.
     */
    private function setup() {
        // Define plugin constants.
        $this->define_constants();

        if ( ! $this->is_requirements_meet() ) {
            return;
        }

        // Instantiate services.
        $this->instantiate();

        // Loaded action.
        do_action( 'upiwc_loaded' );
    }

    /**
     * Check that the WordPress and PHP setup meets the plugin requirements.
     *
     * @return bool
     */
    private function is_requirements_meet() {

        // Check WordPress version.
        if ( version_compare( get_bloginfo( 'version' ), $this->wordpress_version, '<' ) ) {
            /* translators: WordPress Version */
            $this->messages[] = sprintf( esc_html__( 'You are using the outdated WordPress, please update it to version %s or higher.', 'upi-qr-code-payment-for-woocommerce' ), $this->wordpress_version );
        }

        // Check PHP version.
        if ( version_compare( phpversion(), $this->php_version, '<' ) ) {
            /* translators: PHP Version */
            $this->messages[] = sprintf( esc_html__( 'UPI QR Code Payment Gateway requires PHP version %s or above. Please update PHP to run this plugin.', 'upi-qr-code-payment-for-woocommerce' ), $this->php_version );
        }

        if ( empty( $this->messages ) ) {
            return true;
        }

        // Auto-deactivate plugin.
        add_action( 'admin_init', [ $this, 'auto_deactivate' ] );
        add_action( 'admin_notices', [ $this, 'activation_error' ] );

        return false;
    }

    /**
     * Auto-deactivate plugin if requirements are not met, and display a notice.
     */
    public function auto_deactivate() {
        deactivate_plugins( UPIWC_BASENAME );
        if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
            unset( $_GET['activate'] ); // phpcs:ignore
        }
    }

    /**
     * Error notice on plugin activation.
     */
    public function activation_error() {
        ?>
        <div class="notice upiwc-notice notice-error">
            <p>
                <?php echo join( '<br>', $this->messages ); // phpcs:ignore ?>
            </p>
        </div>
        <?php
    }

    /**
     * Define the plugin constants.
     */
    private function define_constants() {
        define( 'UPIWC_VERSION', $this->version );
        define( 'UPIWC_FILE', __FILE__ );
        define( 'UPIWC_PATH', dirname( UPIWC_FILE ) . '/' );
        define( 'UPIWC_URL', plugins_url( '', UPIWC_FILE ) . '/' );
        define( 'UPIWC_BASENAME', plugin_basename( UPIWC_FILE ) );
    }

    /**
     * Instantiate services.
     */
    private function instantiate() {
        // Activation hook.
        register_activation_hook( UPIWC_FILE, 
            function () {
                set_transient( 'upiwc-admin-notice-on-activation', true, 5 );
            } 
        );

        // Deactivation hook.
        register_deactivation_hook( UPIWC_FILE, 
            function () {
                delete_option( 'upiwc_plugin_dismiss_rating_notice' );
                delete_option( 'upiwc_plugin_no_thanks_rating_notice' );
                delete_option( 'upiwc_plugin_installed_time' );
                delete_option( 'upiwc_plugin_dismiss_donate_notice' );
                delete_option( 'upiwc_plugin_no_thanks_donate_notice' );
                delete_option( 'upiwc_plugin_dismissed_time' );
                delete_option( 'upiwc_plugin_dismissed_time_donate' );
            } 
        );

        // Initialize the action and filter hooks.
		$this->init_actions();
    }

    /**
	 * Initialize WordPress action and filter hooks.
	 */
	private function init_actions() {
        // Make sure it is loaded before setup_modules and load_modules.
        add_action( 'plugins_loaded', [ $this, 'localization_setup' ], 9 );

        // Add plugin action links.
        add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
        add_filter( 'plugin_action_links_' . UPIWC_BASENAME, [ $this, 'action_links' ] );

        // Declaring HPOS compatibility.
        add_action( 'before_woocommerce_init', [ $this, 'declare_compatibility' ] );

        // Register payment gateway.
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );

        // Load payment gateway.
        add_action( 'plugins_loaded', [ $this, 'load_gateway' ] );
        add_action( 'woocommerce_blocks_loaded', array( $this, 'block_support' ) );

        // Load admin notices.
        add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_action( 'admin_init', [ $this, 'dismiss_notice' ] );
    }

    /**
	 * Initialize plugin for localization.
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'upi-qr-code-payment-for-woocommerce', false, dirname( UPIWC_BASENAME ) . '/languages' ); 
	}

    /**
	 * Add extra links as row meta on the plugin screen.
	 *
	 * @param  mixed $links Plugin Row Meta.
	 * @param  mixed $file  Plugin Base file.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( UPIWC_BASENAME !== $file ) {
			return $links;
		}

		$more = [
            '<a href="https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/" target="_blank">' . __( 'Support', 'upi-qr-code-payment-for-woocommerce' ) . '</a>',
            '<a href="https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/#faq" target="_blank">' . __( 'FAQ', 'upi-qr-code-payment-for-woocommerce' ) . '</a>',
            '<a href="https://www.sayandatta.co.in/donate" target="_blank">' . __( 'Donate', 'upi-qr-code-payment-for-woocommerce' ) . '</a>',
        ];

		return array_merge( $links, $more );
	}

    /**
	 * Show action links on the plugin screen.
	 *
	 * @param  mixed $links Plugin Action links.
	 * @return array
	 */
    public function action_links( $links ) {
        $links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-upi' ) . '">' . __( 'Settings', 'upi-qr-code-payment-for-woocommerce' ) . '</a>';
        
        return $links;
    }

    /**
	 * Declaring HPOS compatibility
	 */
    public function declare_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', UPIWC_FILE, true );
        }
    }

    /**
	 * Register WooCommerce gateway.
	 *
	 * @param  mixed $links Plugin Action links.
	 * @return array
	 */
    public function register_gateway( $gateways ) {
        $gateways[] = 'UPI_WC_Payment_Gateway'; // class name

        return $gateways;
    }

    /**
	 * Load Payment Gateway.
	 */
    public function load_gateway() {
        if ( class_exists( '\WC_Payment_Gateway' ) ) {
            require_once UPIWC_PATH . 'includes/class-payment.php';
        }
    }

    /**
	 * Registers WooCommerce Blocks integration.
	 */
	public function block_support() {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            require_once UPIWC_PATH . 'includes/blocks/class-blocks-support.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new UPI_WC_Payment_Gateway_Blocks_Support() );
				}
			);
		}
	}

	/**
	 * Show internal admin notices.
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check transient, if available display notice
        if ( get_transient( 'upiwc-admin-notice-on-activation' ) ) { ?>
            <div class="notice notice-success">
                <p><strong><?php printf( __( 'Thanks for installing %1$s v%2$s plugin. Click <a href="%3$s">here</a> to configure plugin settings.', 'upi-qr-code-payment-for-woocommerce' ), 'UPI QR Code Payment Gateway', UPIWC_VERSION, admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-upi' ) ); ?></strong></p>
            </div> <?php
            delete_transient( 'upiwc-admin-notice-on-activation' );
        }

		$show_rating = true;
		if ( $this->calculate_time() > strtotime( '-7 days' )
            || '1' === get_option( 'upiwc_plugin_dismiss_rating_notice' )
            || apply_filters( 'upiwc_plugin_hide_sticky_notice', false ) ) {
			$show_rating = false;
        }
    
		if ( $show_rating ) {
			$dismiss = wp_nonce_url( add_query_arg( 'upiwc_notice_action', 'dismiss_rating' ), 'upiwc_notice_nonce' );
            $no_thanks = wp_nonce_url( add_query_arg( 'upiwc_notice_action', 'no_thanks_rating' ), 'upiwc_notice_nonce' ); ?>
            
            <div class="notice notice-success">
                <p><?php esc_html_e( 'Hey, I noticed you\'ve been using UPI QR Code Payment Gateway for more than 2 week – that’s awesome! Could you please do me a BIG favor and give it a <strong>5-star</strong> rating on WordPress? Just to help me spread the word and boost my motivation.', 'upi-qr-code-payment-for-woocommerce' ); ?></p>
                <p><a href="https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="button button-secondary"><?php esc_html_e( 'Ok, you deserve it', 'upi-qr-code-payment-for-woocommerce' ); ?></a>&nbsp;
                <a href="<?php echo esc_url( $dismiss ); ?>" class="already-did"><strong><?php esc_html_e( 'I already did', 'upi-qr-code-payment-for-woocommerce' ); ?></strong></a>&nbsp;<strong>|</strong>
                <a href="<?php echo esc_url( $no_thanks ); ?>" class="later"><strong><?php esc_html_e( 'Nope&#44; maybe later', 'upi-qr-code-payment-for-woocommerce' ); ?></strong></a></p>
            </div>
			<?php
		}

		$show_donate = true;
		if ( $this->calculate_time() > strtotime( '-10 days' )
            || '1' === get_option( 'upiwc_plugin_dismiss_donate_notice' )
            || apply_filters( 'upiwc_plugin_hide_sticky_donate_notice', false ) ) {
			$show_donate = false;
		}

		if ( $show_donate ) {
			$dismiss = wp_nonce_url( add_query_arg( 'upiwc_notice_action', 'dismiss_donate' ), 'upiwc_notice_nonce' );
            $no_thanks = wp_nonce_url( add_query_arg( 'upiwc_notice_action', 'no_thanks_donate' ), 'upiwc_notice_nonce' ); ?>
            
            <div class="notice notice-success">
                <p><?php esc_html_e( 'Hey, I noticed you\'ve been using UPI QR Code Payment Gateway for more than 2 week – that’s awesome! If you like UPI QR Code Payment Gateway and you are satisfied with the plugin, isn’t that worth a coffee or two? Please consider donating. Donations help me to continue support and development of this free plugin! Thank you very much!', 'upi-qr-code-payment-for-woocommerce' ); ?></p>
                <p><a href="https://www.sayandatta.co.in/donate" target="_blank" class="button button-secondary"><?php esc_html_e( 'Donate Now', 'upi-qr-code-payment-for-woocommerce' ); ?></a>&nbsp;
                <a href="<?php echo esc_url( $dismiss ); ?>" class="already-did"><strong><?php esc_html_e( 'I already donated', 'upi-qr-code-payment-for-woocommerce' ); ?></strong></a>&nbsp;<strong>|</strong>
                <a href="<?php echo esc_url( $no_thanks ); ?>" class="later"><strong><?php esc_html_e( 'Nope&#44; maybe later', 'upi-qr-code-payment-for-woocommerce' ); ?></strong></a></p>
            </div>
			<?php
		}
	}

	/**
	 * Dismiss admin notices.
	 */
	public function dismiss_notice() {
		// Check for Rating Notice
		if ( get_option( 'upiwc_plugin_no_thanks_rating_notice' ) === '1'
			&& get_option( 'upiwc_plugin_dismissed_time' ) <= strtotime( '-10 days' ) ) {
			delete_option( 'upiwc_plugin_dismiss_rating_notice' );
			delete_option( 'upiwc_plugin_no_thanks_rating_notice' );
		}

		// Check for Donate Notice
		if ( get_option( 'upiwc_plugin_no_thanks_donate_notice' ) === '1'
			&& get_option( 'upiwc_plugin_dismissed_time_donate' ) <= strtotime( '-14 days' ) ) {
			delete_option( 'upiwc_plugin_dismiss_donate_notice' );
			delete_option( 'upiwc_plugin_no_thanks_donate_notice' );
		}

		if ( ! isset( $_REQUEST['upiwc_notice_action'] ) || empty( $_REQUEST['upiwc_notice_action'] ) ) {
			return;
		}

		check_admin_referer( 'upiwc_notice_nonce' );

		$notice = sanitize_text_field( $_REQUEST['upiwc_notice_action'] );
		$notice = explode( '_', $notice );
		$notice_type = end( $notice );
		array_pop( $notice );
		$notice_action = join( '_', $notice );

		if ( 'dismiss' === $notice_action ) {
			update_option( 'upiwc_plugin_dismiss_' . $notice_type . '_notice', '1' );
		}
	
		if ( 'no_thanks' === $notice_action ) {
			update_option( 'upiwc_plugin_no_thanks_' . $notice_type . '_notice', '1' );
			update_option( 'upiwc_plugin_dismiss_' . $notice_type . '_notice', '1' );
			if ( 'donate' === $notice_type ) {
				update_option( 'upiwc_plugin_dismissed_time_donate', time() );
			} else {
				update_option( 'upiwc_plugin_dismissed_time', time() );
			}
		}
	
		wp_redirect( remove_query_arg( [ 'upiwc_notice_action', '_wpnonce' ] ) );
		exit;
	}

	/**
	 * Calculate install time.
	 */
	private function calculate_time() {
		$installed_time = get_option( 'upiwc_plugin_installed_time' );
		
		if ( ! $installed_time ) {
            $installed_time = time();
            update_option( 'upiwc_plugin_installed_time', $installed_time );
		}
		
        return $installed_time;
	}
}

/**
 * Returns the main instance of UPIWC to prevent the need to use globals.
 *
 * @return UPIWC
 */
function upiwc() {
    return UPIWC::get();
}

// Start it.
upiwc();