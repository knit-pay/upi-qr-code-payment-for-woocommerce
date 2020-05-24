<?php
/**
 * The admin-facing functionality of the plugin.
 *
 * @package    UPI QR Code Payment for WooCommerce
 * @subpackage Includes
 * @author     Sayan Datta
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */

// add Gateway to woocommerce
add_filter( 'woocommerce_payment_gateways', 'upiwc_woocommerce_payment_add_gateway_class' );

function upiwc_woocommerce_payment_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_UPI_Payment_Gateway'; // class name
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'upiwc_payment_gateway_init' );

function upiwc_payment_gateway_init() {

	// If the WooCommerce payment gateway class is not available nothing will return
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	class WC_UPI_Payment_Gateway extends WC_Payment_Gateway {
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'wc-upi';
			$this->icon               = apply_filters( 'upiwc_custom_gateway_icon', UPI_WOO_PLUGIN_DIR . 'includes/icon/logo.png' );
			$this->has_fields         = false;
			$this->method_title       = __( 'UPI QR Code', 'upi-qr-code-payment-for-woocommerce' );
			$this->method_description = __( 'Allows customers to use UPI mobile app like Paytm, Google Pay, BHIM, PhonePe to pay to your bank account directly using UPI.', 'upi-qr-code-payment-for-woocommerce' );
			$this->order_button_text  = __( 'Proceed to Payment', 'upi-qr-code-payment-for-woocommerce' );

			// Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();
		  
			// Define user set variables
			$this->title                = $this->get_option( 'title' );
			$this->description          = $this->get_option( 'description' );
			$this->instructions         = $this->get_option( 'instructions', $this->description );
			$this->thank_you            = $this->get_option( 'thank_you' );
			$this->payment_status       = $this->get_option( 'payment_status', 'on-hold' );
			$this->name 	            = $this->get_option( 'name' );
			$this->vpa 		            = $this->get_option( 'vpa' );
			$this->pay_button 		    = $this->get_option( 'pay_button' );
			$this->button_text 		    = $this->get_option( 'button_text' );
			$this->qrcode_mobile 		= $this->get_option( 'qrcode_mobile', 'yes' );
			$this->disable_info 		= $this->get_option( 'disable_info', 'no' );
			$this->upi_handle 		    = $this->get_option( 'upi_handle', 'no' );
			$this->transaction_id 		= $this->get_option( 'transaction_id', 'no' );
			$this->require_txn_id 		= $this->get_option( 'require_txn_id', 'no' );
			$this->email_enabled        = $this->get_option( 'email_enabled' );
			$this->email_subject        = $this->get_option( 'email_subject' );
			$this->email_heading        = $this->get_option( 'email_heading' );
			$this->additional_content   = $this->get_option( 'additional_content' );
			$this->default_status       = apply_filters( 'upiwc_process_payment_order_status', 'pending' );
			
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// We need custom JavaScript to obtain the transaction number
	        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

			// thank you page output
			add_action( 'woocommerce_receipt_'.$this->id, array( $this, 'generate_qr_code' ), 4, 1 );

			// verify payment from redirection
            add_action( 'woocommerce_api_upiwc-payment', array( $this, 'capture_payment' ) );

			// Customize on hold email template subject
			add_filter( 'woocommerce_email_subject_customer_on_hold_order', array( $this, 'email_subject_pending_order' ), 10, 3 );

			// Customize on hold email template heading
			add_filter( 'woocommerce_email_heading_customer_on_hold_order', array( $this, 'email_heading_pending_order' ), 10, 3 );

			// Customize on hold email template additional content
			add_filter( 'woocommerce_email_additional_content_customer_on_hold_order', array( $this, 'email_additional_content_pending_order' ), 10, 3 );

			// Customer Emails
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 4 );

			// add support for payment for on hold orders
			add_action( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'on_hold_payment' ), 10, 2 );

			// change wc payment link if exists payment method is QR Code
			add_filter( 'woocommerce_get_checkout_payment_url', array( $this, 'custom_checkout_url' ), 10, 2 );
			
			// add custom text on thankyou page
			add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
			
			if ( ! $this->is_valid_for_use() ) {
                $this->enabled = 'no';
            }
		}

		/**
	     * Check if this gateway is enabled and available in the user's country.
	     *
	     * @return bool
	     */
	    public function is_valid_for_use() {
			if ( get_woocommerce_currency() !== 'INR' ) {
				return false;
			}
	    	return true;
        }
        
        /**
	     * Admin Panel Options.
	     *
	     * @since 1.0.0
	     */
	    public function admin_options() {
	    	if ( $this->is_valid_for_use() ) {
	    		parent::admin_options();
	    	} else {
	    		?>
	    		<div class="inline error">
	    			<p>
	    				<strong><?php esc_html_e( 'Gateway disabled', 'upi-qr-code-payment-for-woocommerce' ); ?></strong>: <?php _e( 'This plugin does not support your store currency. UPI Payment only supports Indian Currency.', 'upi-qr-code-payment-for-woocommerce' ); ?>
	    			</p>
	    		</div>
	    		<?php
	    	}
        }
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable UPI QR Code Payment', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this if you want to collect payment via UPI QR Codes.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'title' => array(
					'title'       => __( 'Title:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => __( 'Pay with UPI QR Code', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => __( 'It uses UPI apps like BHIM, Paytm, Google Pay, PhonePe or any Banking UPI app to make payment.', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the order pay page and emails.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => __( 'Scan the QR Code with any UPI apps like BHIM, Paytm, Google Pay, PhonePe or any Banking UPI app to make payment for this order. After successful payment, enter the UPI Reference ID or Transaction Number and your UPI ID in the next screen and submit the form. We will manually verify this payment against your 12-digits UPI Reference ID or Transaction Number (e.g. 001422121258) and your UPI ID.', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
				),
                'thank_you' => array(
                    'title'       => __( 'Thank You Message:', 'upi-qr-code-payment-for-woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'This displays a message to customer after a successful payment is made.', 'upi-qr-code-payment-for-woocommerce' ),
                    'default'     => __( 'Thank you for your payment. Your transaction has been completed, and your order has been successfully placed. Please check you Email inbox for details. Please check your bank account statement to view transaction details.', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'payment_status' => array(
                    'title'       => __( 'Payment Success Status:', 'upi-qr-code-payment-for-woocommerce' ),
                    'type'        => 'select',
					'description' =>  __( 'Payment action on successful UPI Transaction ID submission.', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
                    'default'     => 'on-hold',
                    'options'     => apply_filters( 'upiwc_settings_order_statuses', array(
						'pending'      => __( 'Pending Payment', 'upi-qr-code-payment-for-woocommerce' ),
						'on-hold'      => __( 'On Hold', 'upi-qr-code-payment-for-woocommerce' ),
						'processing'   => __( 'Processing', 'upi-qr-code-payment-for-woocommerce' ),
						'completed'    => __( 'Completed', 'upi-qr-code-payment-for-woocommerce' )
                    ) )
                ),
				'name' => array(
			    	'title'       => __( 'Your Store or Shop Name:', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'text',
			    	'description' => __( 'Please enter Your Store or Shop name. If you are a person, you can enter your name.', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => get_bloginfo( 'name' ),
			    	'desc_tip'    => true,
				),
			    'vpa' => array(
			    	'title'       => __( 'UPI VPA ID:', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'email',
			    	'description' => __( 'Please enter Your UPI VPA at which you want to collect payments.', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => '',
			    	'desc_tip'    => true,
				),
				'pay_button' => array(
			    	'title'       => __( 'Pay Now Button Text:', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'text',
			    	'description' => __( 'Enter the text to show as the payment button.', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => __( 'Scan & Pay Now', 'upi-qr-code-payment-for-woocommerce' ),
			    	'desc_tip'    => true,
				),
				'button_text' => array(
			    	'title'       => __( 'Button Text (Mobile):', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'text',
			    	'description' => __( 'Enter the button text to show on mobile devices.', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => __( 'Click here to pay through UPI', 'upi-qr-code-payment-for-woocommerce' ),
			    	'desc_tip'    => true,
				),
				'qrcode_mobile' => array(
					'title'       => __( 'Mobile QR Code:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Show QR Code on Mobile', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this if you want to show UPI QR Code on mobile devices.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'disable_info' => array(
					'title'       => __( 'Require Payment Info:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Disable UPI ID Collection', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this if you do want to collect UPI ID or Transaction Number from your customer.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'upi_handle' => array(
					'title'       => __( 'UPI Handle (VPA):', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Show UPI Handle Field', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this if you want to allow for your customer to select UPI handles from predefined list.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
                'transaction_id' => array(
					'title'       => __( 'UPI Transaction ID:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Show Transaction ID Field', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this if you want to collect UPI Transaction ID from your customers.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'require_txn_id' => array(
					'title'       => __( 'Require Transaction ID:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Require Transaction ID Field', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this if you want to require UPI Transaction ID field.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'email' => array(
                    'title'       => __( 'Configure Email', 'upi-qr-code-payment-for-woocommerce' ),
                    'type'        => 'title',
                    'description' => '',
				),
				'email_enabled' => array(
					'title'       => __( 'Enable/Disable:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable Email Notification', 'upi-qr-code-payment-for-woocommerce' ),
					'description' => __( 'Enable this option if you want to send payment link to the customer via email after placing the successful order.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'email_subject' => array(
					'title'       => __( 'Email Subject:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( '{site_title}, {site_address}, {order_date}, {order_number}' ) . '</code>' ),
					'default'     => __( '[{site_title}]: Payment pending #{order_number}', 'upi-qr-code-payment-for-woocommerce' ),
				),
				'email_heading' => array(
					'title'       => __( 'Email Heading:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( '{site_title}, {site_address}, {order_date}, {order_number}' ) . '</code>' ),
					'default'     => __( 'Thank you for your order', 'upi-qr-code-payment-for-woocommerce' ),
				),
				'additional_content' => array(
					'title'       => __( 'Email Body Text:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'This text will be attached to the On Hold email template sent to customer. Use {upi_pay_link} to add the link of payment page.', 'upi-qr-code-payment-for-woocommerce' ),
					'default'     => __( 'Please complete the payment via UPI by going to this link: {upi_pay_link} (ignore if already done).', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
				)
			);
		}

		/*
		 * Custom CSS and JS
		 */
		public function payment_scripts() {
			// exit if we are not on the Order Pay page
			if( ! is_wc_endpoint_url( 'order-pay' ) ) return;
			
	        // if our payment gateway is disabled, we do not have to enqueue JS too
	        if( 'no' === $this->enabled ) {
	        	return;
			}

			$ver = UPI_WOO_PLUGIN_VERSION;
            if( defined( 'UPI_WOO_PLUGIN_ENABLE_DEBUG' ) ) {
                $ver = time();
            }
			
			wp_register_style( 'upiwc-jquery-confirm', plugins_url( 'css/jquery-confirm.min.css' , __FILE__ ), array(), '3.3.4' );
			wp_register_style( 'upiwc-qr-code', plugins_url( 'css/upi.min.css' , __FILE__ ), array( 'upiwc-jquery-confirm' ), $ver );
			wp_register_script( 'upiwc-jquery-confirm-js', plugins_url( 'js/jquery-confirm.min.js' , __FILE__ ), array( 'jquery' ), '3.3.4', true );
		    wp_register_script( 'upiwc-qr-code-js', plugins_url( 'js/easy.qrcode.min.js' , __FILE__ ), array( 'jquery' ), '3.6.0', true );
			wp_register_script( 'upiwc-js', plugins_url( 'js/upi.min.js' , __FILE__ ), array( 'jquery', 'upiwc-qr-code-js', 'upiwc-jquery-confirm-js' ), $ver, true );
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
			
			// Mark as pending (we're awaiting the payment)
			$order->update_status( $this->default_status );

			// add some order notes
			$order->add_order_note( apply_filters( 'upiwc_process_payment_note', __( 'Awaiting UPI Payment!', 'upi-qr-code-payment-for-woocommerce' ), $order ), false );
			
			// update meta
			update_post_meta( $order->get_id(), '_upiwc_order_paid', 'no' );

			// Empty cart
			WC()->cart->empty_cart();

			// check plugin settings
			if( 'yes' === $this->enabled && 'yes' === $this->email_enabled && $order->has_status( 'pending' ) ) {
				// Get an instance of the WC_Email_Customer_On_Hold_Order object
				$wc_email = WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order'];
				
                // Send "New Email" notification
                $wc_email->trigger( $order_id );
			}

			// Return redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> apply_filters( 'upiwc_process_payment_redirect', $order->get_checkout_payment_url( true ), $order )
			);
		}
		
		/**
	     * Show UPI details as html output
	     *
	     * @param WC_Order $order_id Order id.
	     * @return string
	     */
		public function generate_qr_code( $order_id ) {
            // get order object from id
			$order = wc_get_order( $order_id );
            $total = apply_filters( 'upiwc_order_total_amount', $order->get_total(), $order );
			
			// enqueue required css & js files
			wp_enqueue_style( 'upiwc-jquery-confirm' );
			wp_enqueue_style( 'upiwc-qr-code' );
			wp_enqueue_script( 'upiwc-jquery-confirm-js' );
		    wp_enqueue_script( 'upiwc-qr-code-js' );
			wp_enqueue_script( 'upiwc-js' );
			
			// add localize scripts
			wp_localize_script( 'upiwc-js', 'upiwc_params',
                array( 
                    'ajaxurl'               => admin_url( 'admin-ajax.php' ),
					'orderid'               => $order_id,
                    'security'              => wp_create_nonce( 'upi_ref_number_id_'.$order_id ),
					'confirm_message'       => apply_filters( 'upiwc_confirm_payment_message', __( 'Please enter the correct details here. Click Confirm, only after amount deducted from your account. We will manually verify your transaction.', 'upi-qr-code-payment-for-woocommerce' ) ),
					'confirm_redirect'      => apply_filters( 'upiwc_confirm_redirect_message', __( 'Click Confirm, only after amount deducted from your account. We will manually verify your transaction. Are you sure?', 'upi-qr-code-payment-for-woocommerce' ) ),
					'redirect_url'          => $order->get_checkout_order_received_url(),
					'callback_url'          => get_home_url() . '/wc-api/upiwc-payment/',
					'cancel_url'            => $order->get_checkout_payment_url(),
					'disable_info'          => $this->disable_info,
					'payment_status'        => $this->payment_status,
					'show_upi_handle_field' => $this->upi_handle,
					'show_tran_field'       => $this->transaction_id,
					'require_tran_field'    => $this->require_txn_id,
					'app_version'           => UPI_WOO_PLUGIN_VERSION,
                )
			);

			// add html output on payment endpoint
			if( 'yes' === $this->enabled && $order->needs_payment() === true && $order->has_status( $this->default_status ) ) { ?>
			    <section class="woo-upi-section">
				    <h2 class="upiwc-title"><?php echo apply_filters( 'upiwc_payment_title_heading', $this->title ); ?></h2>
					<?php do_action( 'upiwc_after_payment_title', $order ); ?>
					<button id="upiwc-confirm-payment" class="btn button" data-theme="<?php echo apply_filters( 'upiwc_payment_dialog_theme', 'blue' ); ?>"><?php echo $this->pay_button; ?></button>
			    	<button id="upiwc-cancel-payment" class="btn button"><?php _e( 'Cancel', 'upi-qr-code-payment-for-woocommerce' ); ?></button>
					<?php do_action( 'upiwc_after_payment_buttons', $order ); ?>
					<div id="js_qrcode">
					    <div id="upiwc-upi-id" class="upiwc-upi-id"><?php _e( 'UPI ID:', 'upi-qr-code-payment-for-woocommerce' ); ?> <span id="upiwc-upi-id-raw"><?php echo htmlentities( strtoupper( $this->vpa ) ); ?></span></div>
						<?php if ( ! wp_is_mobile() || ( wp_is_mobile() && $this->qrcode_mobile === 'yes' ) ) { ?>
						    <div id="upiwc-qrcode"></div>
						<?php } ?>
						<div id="upiwc-order-total" class="upiwc-order-total"><?php _e( 'Amount to be Paid:', 'upi-qr-code-payment-for-woocommerce' ); ?> <span id="upiwc-order-total-amount">₹<?php echo $total; ?></span></div>
						<?php if ( wp_is_mobile() ) { ?>
						    <div class="jconfirm-buttons">
						        <a href="upi://pay?pa=<?php echo htmlentities( strtolower( $this->vpa ) ); ?>&pn=<?php echo preg_replace('/[^\p{L}\p{N}\s]/u', '', $this->name ); ?>&am=<?php echo $total; ?>&tr=<?php echo $order->get_id(); ?>&tn=<?php _e( 'Order ID', 'upi-qr-code-payment-for-woocommerce' ); ?> <?php echo $order->get_order_number(); ?>&mode=04" onclick="window.onbeforeunload = null;"><button type="button" class="btn btn-dark"><?php echo $this->button_text; ?></button></a>
						    </div>
						<?php } ?>
						<div id="upiwc-description" class="upiwc-description"><?php echo wptexturize( $this->instructions ); ?></div>
					    <?php if ( wp_is_mobile() ) { ?>
						    <input type="hidden" id="data-qr-code" data-width="140" data-height="140" data-link="upi://pay?pa=<?php echo htmlentities( strtolower( $this->vpa ) ); ?>&pn=<?php echo preg_replace('/[^\p{L}\p{N}\s]/u', '', $this->name ); ?>&am=<?php echo $total; ?>&tr=<?php echo $order->get_id(); ?>&tn=<?php _e( 'Order ID', 'upi-qr-code-payment-for-woocommerce' ); ?> <?php echo $order->get_order_number(); ?>&mode=01">
						    <input type="hidden" id="data-dialog-box" data-pay="95%" data-confirm="95%" data-redirect="95%">
						<?php } else { ?>
						    <input type="hidden" id="data-qr-code" data-width="180" data-height="180" data-link="upi://pay?pa=<?php echo htmlentities( strtolower( $this->vpa ) ); ?>&pn=<?php echo preg_replace('/[^\p{L}\p{N}\s]/u', '', $this->name ); ?>&am=<?php echo $total; ?>&tr=<?php echo $order->get_id(); ?>&tn=<?php _e( 'Order ID', 'upi-qr-code-payment-for-woocommerce' ); ?> <?php echo $order->get_order_number(); ?>&mode=01">
							<input type="hidden" id="data-dialog-box" data-pay="60%" data-confirm="50%" data-redirect="40%">
						<?php } ?>
					</div>
				</section><?php
			}
		}

		/**
	     * Process payment verification.
	     */
        public function capture_payment() {
            // get order id
            if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
                // create redirect
                wp_safe_redirect( home_url() );
                exit;
            }

            // check post requests
        	if ( ! isset( $_GET['wc_order_id'] ) ) {
                // create redirect
                wp_safe_redirect( home_url() );
                exit;
            }
        
            // generate order
            $order = wc_get_order( esc_attr( $_GET['wc_order_id'] ) );
            
            // check if it an order
            if ( is_a( $order, 'WC_Order' ) ) {
                $order->update_status( apply_filters( 'upiwc_capture_payment_order_status', $this->payment_status ) );
		        // reduce stock level
		        wc_reduce_stock_levels( $order->get_id() );
		        // set order note
		        $order->add_order_note( __( 'Payment primarily completed. Needs shop owner\'s verification.', 'upi-qr-code-payment-for-woocommerce' ), false );
		        // update post meta
				update_post_meta( $order->get_id(), '_upiwc_order_paid', 'yes' );
				// create redirect
				wp_safe_redirect( $order->get_checkout_order_received_url() );
                exit;
            } else {
				// create redirect
                $title = __( 'Order can\'t be found against this Order ID. If the money debited from your account, please Contact with Site Administrator for further action.', 'upi-qr-code-payment-for-woocommerce' );
                        
                wp_die( $title, get_bloginfo( 'name' ) );
                exit;
			}
        }

        /**
		 * Customize the WC emails template.
		 *
		 * @access public
		 * @param string $formated_subject
		 * @param WC_Order $order
		 * @param object $object
		 */

		public function email_subject_pending_order( $formated_subject, $order, $object ) {
			// We exit for 'order-accepted' custom order status
			if( $this->id === $order->get_payment_method() && 'yes' === $this->enabled && $order->has_status( 'pending' ) ) {
				return $object->format_string( $this->email_subject );
			}

			return $formated_subject;
		}

		/**
		 * Customize the WC emails template.
		 *
		 * @access public
		 * @param string $formated_subject
		 * @param WC_Order $order
		 * @param object $object
		 */
		public function email_heading_pending_order( $formated_heading, $order, $object ) {
			// We exit for 'order-accepted' custom order status
			if( $this->id === $order->get_payment_method() && 'yes' === $this->enabled && $order->has_status( 'pending' ) ) {
				return $object->format_string( $this->email_heading );
			}

			return $formated_heading;
		}

		/**
		 * Customize the WC emails template.
		 *
		 * @access public
		 * @param string $formated_subject
		 * @param WC_Order $order
		 * @param object $object
		 */
		public function email_additional_content_pending_order( $formated_additional_content, $order, $object ) {
			// We exit for 'order-accepted' custom order status
			if( $this->id === $order->get_payment_method() && 'yes' === $this->enabled && $order->has_status( 'pending' ) ) {
                return $object->format_string( str_replace( '{upi_pay_link}', $order->get_checkout_payment_url( true ), $this->additional_content ) );
			}

			return $formated_additional_content;
		}

		/**
	     * Custom order received text.
	     *
	     * @param string   $text Default text.
	     * @param WC_Order $order Order data.
	     * @return string
	     */
	    public function order_received_text( $text, $order ) {
	    	if ( $this->id === $order->get_payment_method() && ! empty( $this->thank_you ) ) {
	    		return esc_html( $this->thank_you );
	    	}
    
	    	return $text;
        }

		/**
	     * Custom checkout URL.
	     *
	     * @param string   $url Default URL.
	     * @param WC_Order $order Order data.
	     * @return string
	     */
	    public function custom_checkout_url( $url, $order ) {
	    	if ( $this->id === $order->get_payment_method() && ( ( $order->has_status( 'on-hold' ) && $this->default_status === 'on-hold' ) || ( $order->has_status( 'pending' ) && apply_filters( 'upiwc_custom_checkout_url', false ) ) ) ) {
	    		return esc_url( remove_query_arg( 'pay_for_order', $url ) );
	    	}
    
	    	return $url;
		}

		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool     $sent_to_admin
		 * @param bool     $plain_text
		 * @param object   $email
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text, $email ) {
		    // check upi gateway name
			if( 'yes' === $this->enabled && 'yes' === $this->email_enabled && ! empty( $this->additional_content ) && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( str_replace( '{upi_pay_link}', $order->get_checkout_payment_url( true ), $this->additional_content ) ) ) . PHP_EOL;
			}
		}

		/**
	     * Custom order received text.
	     *
	     * @param string   $statuses  Default status.
	     * @param WC_Order $order     Order data.
	     * @return string
	     */
		public function on_hold_payment( $statuses, $order ) {
			if( $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) && $order->get_meta( '_upiwc_order_paid', true ) !== 'yes' && $this->default_status === 'on-hold' ) {
				$statuses[] = 'on-hold';
			}
		
			return $statuses;
		}
    }
}

// add ajax functions
add_action( 'wp_ajax_process_upi_id_verification',  'upiwc_verify_upi_transaction' );
add_action( 'wp_ajax_nopriv_process_upi_id_verification', 'upiwc_verify_upi_transaction' );

function upiwc_verify_upi_transaction() {
	// check post requests
	if ( isset( $_POST['orderID'], $_POST['upiid'], $_POST['tranid'], $_POST['redirect'], $_POST['status'] ) ) {
		// get order id
		$orderID = sanitize_text_field( $_POST['orderID'] );
		// security check
		check_ajax_referer( 'upi_ref_number_id_'.$orderID, 'security' );
		// get upi id
		$upiID = sanitize_text_field( $_POST['upiid'] );
		// get transaction id
		$tranID = !empty( $_POST['tranid'] ) ? sanitize_text_field( $_POST['tranid'] ) : '';
		// get new status
		$status = sanitize_text_field( $_POST['status'] );
		// get redirect url
		$url = esc_url( $_POST['redirect'] );
	    // get order from order id
		$order = wc_get_order( $orderID );
		// check if it an order
		if( is_a( $order, 'WC_Order' ) ) {
		    // update the payment reference
		    $order->set_transaction_id( esc_attr( $tranID ) );
		    // Mark as on-hold (we're verifying the payment manually)
		    $order->update_status( apply_filters( 'upiwc_capture_payment_order_status', $status ) );
		    // reduce stock level
		    wc_reduce_stock_levels( $order->get_id() );
		    // set order note
		    $order->add_order_note( apply_filters( 'upiwc_capture_payment_note', sprintf( __( 'UPI ID: %1$s%3$sUPI Transaction ID: %2$s', 'upi-qr-code-payment-for-woocommerce' ), $upiID, ! empty( $tranID ) ? $tranID : 'N/A', '<br>' ), $order ), false );
		    // update post meta
		    update_post_meta( $order->get_id(), '_upiwc_order_paid', 'yes' );
		    // send response
		    wp_send_json_success( array(
		    	'message' => apply_filters( 'upiwc_capture_payment_redirect_notice', __( 'Thank you for shopping with us. We will contact you shortly.<br>We are redirecting you in a moment...', 'upi-qr-code-payment-for-woocommerce' ) ),
		    	'redirect' => apply_filters( 'upiwc_capture_payment_redirect', $url )
			) );
		} else {
			wp_send_json_error();
		}
	} else {
		wp_send_json_error();
	}
	die();
}