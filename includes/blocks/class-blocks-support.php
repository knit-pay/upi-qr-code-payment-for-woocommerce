<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * UPI Payments Blocks integration
 *
 * @since 1.4.0
 */
final class UPI_WC_Payment_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'wc-upi';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", [] );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = 'includes/blocks/assets/blocks.js';
		$script_asset_path = UPIWC_PATH . 'includes/blocks/assets/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => UPIWC_VERSION
			);
		$script_url        = UPIWC_URL . $script_path;

		wp_register_script(
			'upiwc-payment-blocks',
			$script_url,
			$script_asset[ 'dependencies' ],
			$script_asset[ 'version' ],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'upiwc-payment-blocks', 'upi-qr-code-payment-for-woocommerce', UPIWC_PATH . 'languages/' );
		}

		return [ 'upiwc-payment-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$handles = array_unique( apply_filters( 'upiwc_upi_handle_list', array( 'airtel', 'airtelpaymentsbank', 'apb', 'apl', 'allbank', 'albk', 'allahabadbank', 'andb', 'axisgo', 'axis', 'axisbank', 'axisb', 'okaxis', 'abfspay', 'axl', 'barodampay', 'barodapay', 'boi', 'cnrb', 'csbpay', 'csbcash', 'centralbank', 'cbin', 'cboi', 'cub', 'dbs', 'dcb', 'dcbbank', 'denabank', 'equitas', 'federal', 'fbl', 'finobank', 'hdfcbank', 'payzapp', 'okhdfcbank', 'rajgovhdfcbank', 'hsbc', 'imobile', 'pockets', 'ezeepay', 'eazypay', 'idbi', 'idbibank', 'idfc', 'idfcbank', 'idfcnetc', 'cmsidfc', 'indianbank', 'indbank', 'indianbk', 'iob', 'indus', 'indusind', 'icici', 'myicici', 'okicici', 'ikwik', 'ibl', 'jkb', 'jsbp', 'kbl', 'karb', 'kbl052', 'kvb', 'karurvysyabank', 'kvbank', 'kotak', 'kaypay', 'kmb', 'kmbl', 'okbizaxis', 'obc', 'paytm', 'pingpay', 'psb', 'pnb', 'sib', 'srcb', 'sc', 'scmobile', 'scb', 'scbl', 'sbi', 'oksbi', 'syndicate', 'syndbank', 'synd', 'lvb', 'lvbank', 'rbl', 'tjsb', 'uco', 'unionbankofindia', 'unionbank', 'uboi', 'ubi', 'united', 'utbi', 'upi', 'vjb', 'vijb', 'vijayabank', 'ubi', 'yesbank', 'ybl', 'yesbankltd' ) ) );
		sort( $handles );

		$handles = array_map( function( $value ) {
			return [
				'label' => '@' . $value,
				'value' => $value,
			];
		}, $handles );

		$placeholder = ( $this->get_setting( 'upi_address' ) === 'show_handle' ) ? 'mobilenumber' : 'mobilenumber@oksbi';
		$placeholder = apply_filters( 'upiwc_upi_address_placeholder', $placeholder );

		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'upi_address' => $this->get_setting( 'upi_address' ),
			'require_upi' => $this->get_setting( 'require_upi' ),
			'supports'    => $this->get_supported_features(),
			'button_text' => apply_filters( 'upiwc_order_button_text', __( 'Proceed to Payment', 'upi-qr-code-payment-for-woocommerce' ) ),
			'placeholder' => $placeholder,
			'handles'     => $handles
		];
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		
		if ( isset( $gateways[ $this->name ] ) ) {
			$gateway = $gateways[ $this->name ];

			return array_filter( $gateway->supports, [ $gateway, 'supports' ] );
		}

		return [];
	}
}
