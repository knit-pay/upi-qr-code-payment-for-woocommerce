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
			$this->button_text 		    = $this->get_option( 'button_text' );
			$this->email_enabled        = $this->get_option( 'email_enabled' );
			$this->email_subject        = $this->get_option( 'email_subject' );
			$this->email_heading        = $this->get_option( 'email_heading' );
			$this->additional_content   = $this->get_option( 'additional_content' );
			
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// We need custom JavaScript to obtain the transaction number
	        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

			// thank you page output
			add_action( 'woocommerce_receipt_'.$this->id, array( $this, 'upi_payment_qr_code_pay' ), 4, 1 );

			// Customize on hold email template subject
			add_filter( 'woocommerce_email_subject_customer_on_hold_order', array( $this, 'email_subject_pending_order' ), 10, 3 );

			// Customize on hold email template heading
			add_filter( 'woocommerce_email_heading_customer_on_hold_order', array( $this, 'email_heading_pending_order' ), 10, 3 );

			// Customize on hold email template additional content
			add_filter( 'woocommerce_email_additional_content_customer_on_hold_order', array( $this, 'email_additional_content_pending_order' ), 10, 3 );

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
	     * - Options for bits like 'title' and availability on a country-by-country basis.
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
			$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( '{site_title}, {site_address}, {order_date}, {order_number}' ) . '</code>' );
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
					'default'     => __( 'Scan the QR Code with any UPI apps like BHIM, Paytm, Google Pay, PhonePe or any Banking UPI app to make payment for this order. After successful payment, there will be a <strong>UPI Reference ID / Transaction Number</strong> mentioned on that page. Please enter the UPI Reference ID or Transaction Number in the below field. We will manually verify this payment against your 12-digits UPI Reference ID or Transaction Number (e.g. 001422121258).', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
				),
                'thank_you' => array(
                    'title'       => __( 'Thank You Message:', 'upi-qr-code-payment-for-woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'This displays a message to customer after a successful payment is made.', 'upi-qr-code-payment-for-woocommerce' ),
                    'desc_tip'    => true,
                    'default'     => __( 'Thank you for your payment. Your transaction has been completed, and your order has been successfully placed. Please check you Email inbox for details. You can view your bank account to view transaction details.', 'upi-qr-code-payment-for-woocommerce' ),
				),
				'payment_status' => array(
                    'title'       => __( 'Payment Success Status:', 'upi-qr-code-payment-for-woocommerce' ),
                    'type'        => 'select',
					'description' =>  __( 'Payment action on successful UPI Transaction ID submission.', 'upi-qr-code-payment-for-woocommerce' ),
					'desc_tip'    => true,
                    'default'     => 'on-hold',
                    'options'     => array(
						'pending'      => __( 'Pending Payment', 'upi-qr-code-payment-for-woocommerce' ),
						'on-hold'      => __( 'On Hold', 'upi-qr-code-payment-for-woocommerce' ),
						'processing'   => __( 'Processing', 'upi-qr-code-payment-for-woocommerce' ),
						'completed'    => __( 'Completed', 'upi-qr-code-payment-for-woocommerce' )
                    )
                ),
				'name' => array(
			    	'title'       => __( 'Your Store Name:', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'text',
			    	'description' => __( 'Please enter Your Store name', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => get_bloginfo( 'name' ),
			    	'desc_tip'    => true,
				),
			    'vpa' => array(
			    	'title'       => __( 'UPI VPA ID:', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'email',
			    	'description' => __( 'Please enter Your UPI VPA', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => '',
			    	'desc_tip'    => true,
				),
				'button_text' => array(
			    	'title'       => __( 'Button Text (Mobile):', 'upi-qr-code-payment-for-woocommerce' ),
			    	'type'        => 'text',
			    	'description' => __( 'Enter the button text to show on mobile devices.', 'upi-qr-code-payment-for-woocommerce' ),
			    	'default'     => __( 'Click here to pay through UPI', 'upi-qr-code-payment-for-woocommerce' ),
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
					'description' => $placeholder_text,
					'default'     => __( '[{site_title}]: Payment pending #{order_number}', 'upi-qr-code-payment-for-woocommerce' ),
				),
				'email_heading' => array(
					'title'       => __( 'Email Heading:', 'upi-qr-code-payment-for-woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
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
			// exit if we are not on the Thank You page
			if( ! is_wc_endpoint_url( 'order-pay' ) ) return;
			
	        // if our payment gateway is disabled, we do not have to enqueue JS too
	        if( 'no' === $this->enabled ) {
	        	return;
			}

			$ver = UPI_WOO_PLUGIN_VERSION;
            if( defined( 'UPI_WOO_PLUGIN_ENABLE_DEBUG' ) ) {
                $ver = time();
            }
			
			wp_enqueue_style( 'upi-qr-code', plugins_url( 'css/upi.min.css' , __FILE__ ), array(), $ver );
			
			wp_enqueue_script( 'qr-code-js', plugins_url( 'js/qrcode.min.js' , __FILE__ ), array( 'jquery' ), null, true );
			wp_enqueue_script( 'upi-js', plugins_url( 'js/upi.min.js' , __FILE__ ), array( 'jquery', 'qr-code-js' ), $ver, true );
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
			$order->update_status( apply_filters( 'upiwc_process_payment_order_status', 'pending' ) );

			// add some order notes
			$order->add_order_note( apply_filters( 'upiwc_process_payment_note', __( 'Awaiting UPI Payment!', 'upi-qr-code-payment-for-woocommerce' ), $order ), false );
			
			// Empty cart
			WC()->cart->empty_cart();

			// check plugin settings
			if( 'yes' === $this->enabled && 'yes' === $this->email_enabled ) {
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
		public function upi_payment_qr_code_pay( $order_id ) {
            // get order object from id
			$order = wc_get_order( $order_id );

			$order_billing_first_name = $order->get_billing_first_name();
			$grand_total = $order->get_total();
		
			$payment_gateway = wc_get_payment_gateway_by_order( $order_id );
		
			$shopvpa = $payment_gateway->vpa;
			$shopname = $payment_gateway->name;

			// add localize scripts
			wp_localize_script( 'upi-js', 'woo_upi_ajax_data',
                array( 
                    'ajaxurl'          => admin_url( 'admin-ajax.php' ),
					'orderid'          => $order_id,
                    'security'         => wp_create_nonce( 'upi_ref_number_id_'.$order_id ),
                    'verify_number'    => apply_filters( 'upiwc_verify_id_format_message', __( 'Only numbers or digits are allowed!', 'upi-qr-code-payment-for-woocommerce' ) ),
                    'verify_start'     => apply_filters( 'upiwc_id_verify_start_message', __( 'Please wait while we are verifying the UPI Reference Number...', 'upi-qr-code-payment-for-woocommerce' ) ),
                    'verify_failed'    => apply_filters( 'upiwc_id_verify_failed_message', __( 'BAD_REQUEST_ERROR: Order ID or Transaction ID not found. Reloading...', 'upi-qr-code-payment-for-woocommerce' ) ),
                    'verify_error'     => apply_filters( 'upiwc_id_verify_error_message', __( 'Please contact with site adminitrator for further assistance.', 'upi-qr-code-payment-for-woocommerce' ) ),
                    'validate_number'  => apply_filters( 'upiwc_id_validate_number_message', __( 'This field must contains a valid 12 digits UPI Refernce Number.', 'upi-qr-code-payment-for-woocommerce' ) ),
                    'app_version'      => UPI_WOO_PLUGIN_VERSION,
                )
			);

			// add html output on payment endpoint
			if( $order->needs_payment() && 'yes' === $this->enabled && $order->has_status( 'pending' ) ) { ?>
			    <section class="woocommerce-order-details woo-upi-section">
					<h2 class="woocommerce-order-details__title"><?php echo apply_filters( 'upiwc_payment_title_heading', $this->title ); ?></h2>
					<table class="woocommerce-table woocommerce-table--order-details shop_table qrcode-table order_details woo-upi-table">
			    		<tbody>
			    			<tr class="woocommerce-table__line-item order_item">
			    				<td class="woocommerce-table__product-name product-name">
			    					<?php if( ! wp_is_mobile() ) { ?>
			    					    <div id="qrcode" style="width:180px; height:180px;margin:0 auto;"></div>
									    <input type="hidden" id="data-qr-code" data-width="180" data-height="180" data-link="upi://pay?pa=<?php echo strtolower( $shopvpa ); ?>&pn=<?php echo $shopname; ?>&am=<?php echo $grand_total; ?>&cu=INR&tr=<?php echo $order->get_id(); ?>&tn=<?php _e( 'OrderID:', 'upi-qr-code-payment-for-woocommerce' ); ?><?php echo $order->get_id(); ?>&mode=01">
			    					<?php } else { ?>
			    					    <div id="qrcode" style="width:250px; height:250px;margin:0 auto;"></div>
			    					    <div style="text-align: center;margin:0 auto;padding-top: 10px;">
			    					        <a class="button btn" href="upi://pay?pa=<?php echo strtolower( $shopvpa ); ?>&pn=<?php echo $shopname; ?>&am=<?php echo $grand_total; ?>&cu=INR&tr=<?php echo $order->get_id(); ?>&tn=<?php _e( 'OrderID: ', 'upi-qr-code-payment-for-woocommerce' ); ?><?php echo $order->get_id(); ?>&mode=00"><?php echo $this->button_text; ?></a>
			    					    </div>
										<input type="hidden" id="data-qr-code" data-width="250" data-height="250" data-link="upi://pay?pa=<?php echo strtolower( $shopvpa ); ?>&pn=<?php echo $shopname; ?>&am=<?php echo $grand_total; ?>&cu=INR&tr=<?php echo $order->get_id(); ?>&tn=<?php _e( 'OrderID:', 'upi-qr-code-payment-for-woocommerce' ); ?><?php echo $order->get_id(); ?>&mode=00">
			    					<?php } ?>
                                </td>
			    				<td class="woocommerce-table__product-total product-total qrcode-info" style="text-align: justify">
			    					<div class="upi-description" style="text-transform: none;"><?php echo wpautop( wptexturize( $this->instructions ) ); ?>
			    						<form id="upi-ref-number" style="margin: -10px 0px 10px 0px;">
			    							<div style="display:inline-block;">
			    								<input type="text" id="upi-ref-num" class="woo-upi-ref-id" name="upi_ref_number" style="width: 250px;vertical-align: middle;" placeholder="<?php _e( 'Enter the UPI Reference ID', 'upi-qr-code-payment-for-woocommerce' ); ?>" title="<?php _e( 'Please enter the 12-digits UPI Reference ID here.', 'upi-qr-code-payment-for-woocommerce' ); ?>" maxlength="12" required="required">
			    							</div>
			    							<div style="display:inline-block;">
			    								<input type="submit" id="upi-send" name="submit" value="Submit" style="vertical-align: middle;">
			    							</div>
			    						</form>
			    						<div class="upi-order-status"></div>
			    					</div>
			    				</td>
			    			</tr>
			    		</tbody>
			    	</table>
			    </section><?php
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
	    	if ( $this->id === $order->get_payment_method() && $order->has_status( 'pending' ) && apply_filters( 'upiwc_custom_checkout_url', true ) ) {
	    		return esc_url( remove_query_arg( 'pay_for_order', $url ) );
	    	}
    
	    	return $url;
		}
    } // end 
}

// add ajax functions
add_action( 'wp_ajax_process_collect_upi_id',  'woo_collect_upi_ref_id' );
add_action( 'wp_ajax_nopriv_process_collect_upi_id', 'woo_collect_upi_ref_id' );

function woo_collect_upi_ref_id() {
	// If the WooCommerce payment gateway extended class is not available nothing will return
	if ( ! class_exists( 'WC_UPI_Payment_Gateway' ) ) return;

	if ( isset( $_POST['orderID'] ) && isset( $_POST['tranid'] ) ) {
		// Access outside of class
        $gateway = new WC_UPI_Payment_Gateway();
		$orderID = sanitize_text_field( $_POST['orderID'] );
		$tranID = sanitize_text_field( $_POST['tranid'] );
		// security check
		check_ajax_referer( 'upi_ref_number_id_'.$orderID, 'security' );
	 
		$order = wc_get_order( $_POST['orderID'] );
		// update the payment reference
		$order->set_transaction_id( esc_attr( $tranID ) );
		// Mark as on-hold (we're verifying the payment manually)
		$order->update_status( apply_filters( 'upiwc_capture_payment_order_status', $gateway->payment_status ) );
		// reduce stock level
		wc_reduce_stock_levels( $order->get_id() );
		// set order note
		$order->add_order_note( apply_filters( 'upiwc_capture_payment_note', __( 'UPI Transaction ID: ', 'upi-qr-code-payment-for-woocommerce' ).$tranID, $order ), false );
		
		wp_send_json_success( array(
			'message' => apply_filters( 'upiwc_capture_payment_redirect_notice', __( 'UPI Reference ID Verified Successfully! Please wait, we are redirecting you in a moment...', 'upi-qr-code-payment-for-woocommerce' ) ),
			'redirect' => apply_filters( 'upiwc_capture_payment_redirect', $order->get_checkout_order_received_url() )
		) );
	} else {
		wp_send_json_error();
	}
	die();
}