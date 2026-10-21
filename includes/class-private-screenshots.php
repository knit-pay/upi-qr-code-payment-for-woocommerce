<?php
/**
 * Private screenshot storage for UPI QR Code Payment Gateway.
 *
 * Payment screenshots contain the customer's payment information (name, UPI handle,
 * transaction references, amounts), so they are never stored in the public Media
 * Library. Files are kept under `uploads/upiwc-private/` behind server access rules
 * with randomly generated names and are served only through an authenticated
 * endpoint that requires order-editing capability.
 *
 * Tier 1 protects screenshots uploaded by versions <= 1.6.3 (they stay in the Media
 * Library, but are hidden from the REST API and front-end attachment pages).
 * Tier 2 stores all new screenshots privately from the moment of upload.
 *
 * @category WooCommerce
 * @package  UPI_QR_Code_Payment_Gateway
 * @author   Team KnitPay
 * @license  http://www.gnu.org/licenses/ GNU General Public License
 * @version  1.7.0
 * @link     https://wordpress.org/plugins/upi-qr-code-payment-for-woocommerce/
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles private storage and access control for payment screenshots.
 */
final class UPIWC_Private_Screenshots {

	/**
	 * Order meta: path (relative to the uploads base directory) of the stored file.
	 *
	 * @var string
	 */
	const META_FILE = '_upiwc_screenshot_file';

	/**
	 * Attachment meta marker for legacy screenshots (uploaded by versions <= 1.6.3).
	 *
	 * @var string
	 */
	const META_MARK = '_upiwc_private_screenshot';

	/**
	 * Attachment meta: comma-separated IDs of the orders storing a legacy screenshot.
	 *
	 * Maintained by the one-time routine; order deletion removes its own entry
	 * and deletes the attachment only when no owners remain.
	 *
	 * @var string
	 */
	const META_OWNER = '_upiwc_screenshot_order';

	/**
	 * Order meta used by versions <= 1.6.3 to store an attachment ID.
	 *
	 * @var string
	 */
	const META_LEGACY_ATT = '_upiwc_order_attachment_id';

	/**
	 * Option that records completion of the one-time legacy protection routine.
	 *
	 * @var string
	 */
	const OPTION_MIGRATED = 'upiwc_private_screenshots_protected';

	/**
	 * Directory (relative to the uploads directory) holding private screenshots.
	 *
	 * @var string
	 */
	const PRIVATE_DIR = 'upiwc-private';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Tier 2: authenticated download endpoint. Deliberately no `_nopriv_` hook:
		// only authenticated users with shop order capabilities can reach it.
		add_action( 'wp_ajax_upiwc_screenshot', array( __CLASS__, 'handle_download_request' ) );

		// Tier 1: remove legacy screenshots from anonymous REST collections.
		add_filter( 'rest_attachment_query', array( __CLASS__, 'rest_exclude_private_collection' ), 20, 3 );

		// Tier 1: guard the individual REST media item endpoint.
		add_filter( 'rest_prepare_attachment', array( __CLASS__, 'rest_guard_item' ), 20, 3 );

		// Tier 1: guard front-end attachment pages (`?attachment_id=` and permalinks).
		// Priority 1 runs before core's redirect_canonical, which would otherwise
		// redirect the attachment page straight to the file URL.
		add_action( 'template_redirect', array( __CLASS__, 'front_end_guard' ), 1 );

		// Tier 1: one-time protection routine for screenshots uploaded by older versions.
		add_action( 'admin_init', array( __CLASS__, 'maybe_protect_legacy' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'maybe_protect_legacy' ) );

		// Remove the screenshot when its order is permanently deleted
		// (fires from both the classic and HPOS order data stores).
		add_action( 'woocommerce_before_delete_order', array( __CLASS__, 'remove_screenshot_for_order' ), 10, 2 );

		// WordPress personal data export and erasure (Tools > Export/Erase Personal Data).
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Store a customer uploaded payment screenshot privately (Tier 2).
	 *
	 * Replaces the previous `media_handle_upload()` flow: no attachment post is
	 * created, the file never touches the public Media Library, and its name is
	 * server generated.
	 *
	 * Adds an order note describing the result. Metadata is staged on the order
	 * object; the caller persists it with `WC_Order::save()`.
	 *
	 * @param WC_Order $order Order being captured.
	 * @return void
	 */
	public static function handle_payment_screenshot( $order ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce is verified in the caller (order bound check).
		if ( empty( $_FILES['upiwc_file'] ) || ! is_array( $_FILES['upiwc_file'] ) ) {
			// phpcs:enable
			return;
		}

		$file = $_FILES['upiwc_file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by the caller; validated line by line below.

		if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$order->add_order_note( __( 'Screenshot upload failed. Please try again.', 'upi-qr-code-payment-for-woocommerce' ), false );
			return;
		}

		if ( empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
			$order->add_order_note( __( 'Screenshot upload failed. Please try again.', 'upi-qr-code-payment-for-woocommerce' ), false );
			return;
		}

		// Validate the real file type, never trust the browser-supplied MIME value.
		if ( ! function_exists( 'wp_check_filetype_and_ext' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
		);

		$check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( wp_unslash( (string) $file['name'] ) ), $allowed_mimes );

		$ext = '';
		if ( 'image/jpeg' === $check['type'] ) {
			$ext = 'jpg';
		} elseif ( 'image/png' === $check['type'] ) {
			$ext = 'png';
		}

		if ( empty( $ext ) ) {
			$order->add_order_note( __( 'File type is not valid!', 'upi-qr-code-payment-for-woocommerce' ), false );
			return;
		}

		// Size limit.
		$max_bytes = (int) apply_filters( 'upiwc_screenshot_max_size', 5 * MB_IN_BYTES );
		if ( empty( $file['size'] ) || (int) $file['size'] > $max_bytes ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: maximum allowed file size (e.g. 5 MB) */
					__( 'Screenshot upload rejected. Maximum allowed size is %s.', 'upi-qr-code-payment-for-woocommerce' ),
					size_format( $max_bytes, 1 )
				),
				false
			);
			return;
		}

		$stored = self::store_uploaded_file( $file['tmp_name'], $ext );

		if ( is_wp_error( $stored ) ) {
			$order->add_order_note( $stored->get_error_message(), false );
			return;
		}

		$order->update_meta_data( self::META_FILE, $stored );

		// Keep the legacy meta key present (empty) so third-party code reading it
		// does not break; screenshots are no longer WordPress attachments.
		$order->update_meta_data( self::META_LEGACY_ATT, '' );

		$order->add_order_note(
			sprintf(
				'%s <a href="%s" target="_blank">%s</a>',
				__( 'Screenshot uploaded successfully (stored privately).', 'upi-qr-code-payment-for-woocommerce' ),
				esc_url( self::download_url( $order->get_id() ) ),
				__( 'View', 'upi-qr-code-payment-for-woocommerce' )
			),
			false
		);
	}

	/**
	 * Move an uploaded file into the protected directory with a server generated name.
	 *
	 * @param string $tmp_path PHP upload tmp path.
	 * @param string $ext      Extension, one of `jpg` or `png`.
	 * @return string|WP_Error Relative path (against the uploads directory) or error.
	 */
	private static function store_uploaded_file( $tmp_path, $ext ) {
		if ( ! is_uploaded_file( $tmp_path ) ) {
			return new WP_Error( 'upiwc_screenshot_invalid_upload', __( 'Screenshot upload failed. Please try again.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		$dir = self::get_protected_dir( gmdate( 'Y' ) . '/' . gmdate( 'm' ) );

		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$filename   = strtolower( wp_generate_password( 24, false, false ) ) . '.' . $ext;
		$destination = $dir['path'] . '/' . $filename;

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- move fail is handled below.
		if ( ! @move_uploaded_file( $tmp_path, $destination ) ) {
			return new WP_Error( 'upiwc_screenshot_move_failed', __( 'Screenshot upload failed: could not store the file. Please contact the administrator.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		clearstatcache();

		return $dir['relative'] . '/' . $filename;
	}

	/**
	 * Ensure the protected directory (and its access guard files) exist.
	 *
	 * @param string $sub Sub path, e.g. `2026/10`.
	 * @return array|WP_Error Array with `path` (absolute) and `relative` (against uploads dir), or error.
	 */
	private static function get_protected_dir( $sub ) {
		$uploads = wp_get_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'upiwc_screenshot_no_uploads_dir', __( 'Screenshot upload failed: uploads directory is not available.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		$relative = self::PRIVATE_DIR . '/' . trim( $sub, '/' );
		$path     = $uploads['basedir'] . '/' . $relative;

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- mkdir fail is handled below.
		if ( ! wp_mkdir_p( $path ) ) {
			return new WP_Error( 'upiwc_screenshot_mkdir_failed', __( 'Screenshot upload failed: could not create the private directory.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		self::write_access_guards( $uploads['basedir'] . '/' . self::PRIVATE_DIR );

		return array(
			'path'     => $path,
			'relative' => $relative,
		);
	}

	/**
	 * Write files that deny direct web access to the private directory.
	 *
	 * Never overwrites existing files, so a site may keep customised rules.
	 *
	 * @param string $private_root Absolute path of `upiwc-private`.
	 * @return void
	 */
	private static function write_access_guards( $private_root ) {
		$htaccess = $private_root . '/.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- guard file creation.
				$htaccess,
				"Options -Indexes\n"
				. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n"
			);
		}

		$web_config = $private_root . '/web.config';

		if ( ! file_exists( $web_config ) ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$web_config,
				"<configuration>\n\t<system.webServer>\n\t\t<security>\n\t\t\t<requestFiltering>\n\t\t\t\t<hiddenSegments>\n\t\t\t\t\t<add segment=\"" . self::PRIVATE_DIR . "\" />\n\t\t\t\t</hiddenSegments>\n\t\t\t</requestFiltering>\n\t\t</security>\n\t</system.webServer>\n</configuration>\n"
			);
		}

		$index = $private_root . '/index.php';

		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/**
	 * Build the authenticated download URL for an order screenshot.
	 *
	 * Uses a user-independent signed token (HMAC of the order ID) instead of a
	 * WordPress nonce: the link is generated inside the customer's capture
	 * request under a different user context than the admin who later opens it,
	 * where a user-bound nonce would fail. The endpoint is registered for
	 * authenticated admin-ajax users only and still checks `edit_shop_orders`,
	 * so the token acts as hardening, not as the access boundary.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public static function download_url( $order_id ) {
		$order_id = absint( $order_id );

		return add_query_arg(
			array(
				'action' => 'upiwc_screenshot',
				'order'  => $order_id,
				'token'  => self::order_token( $order_id ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Compute the stable, user-independent token for an order screenshot URL.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private static function order_token( $order_id ) {
		return substr( hash_hmac( 'sha256', 'upiwc_screenshot|' . absint( $order_id ), wp_salt( 'auth' ) ), 0, 24 );
	}

	/**
	 * Serve a screenshot through admin-ajax.php (authenticated only).
	 *
	 * Security checks, in order: signed order token (hardening), then the
	 * `edit_shop_orders` capability (the actual access boundary).
	 *
	 * @return void
	 */
	public static function handle_download_request() {
		// This callback is the verifier itself: it validates the signed order token
		// and the `edit_shop_orders` capability before touching any data.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$order_id = isset( $_GET['order'] ) ? absint( wp_unslash( $_GET['order'] ) ) : 0;
		$token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		// phpcs:enable

		if ( empty( $order_id ) || ! hash_equals( self::order_token( $order_id ), (string) $token ) ) {
			wp_die(
				esc_html__( 'This screenshot link is invalid or expired. Open the order again to get a fresh link.', 'upi-qr-code-payment-for-woocommerce' ),
				'',
				array( 'response' => 403 )
			);
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) { // phpcs:ignore WordPress.WP.Capabilities -- WooCommerce capability, resolved via map_meta_cap when WooCommerce is active.
			wp_die(
				esc_html__( 'You are not allowed to view this screenshot.', 'upi-qr-code-payment-for-woocommerce' ),
				'',
				array( 'response' => 403 )
			);
		}

		$payload = self::resolve_screenshot( $order_id );

		if ( is_wp_error( $payload ) ) {
			wp_die(
				esc_html( $payload->get_error_message() ),
				'',
				array( 'response' => 404 )
			);
		}

		nocache_headers();
		header( 'Content-Type: ' . $payload['type'] );
		header( 'Content-Disposition: inline; filename="' . $payload['name'] . '"' );
		header( 'Content-Length: ' . (string) filesize( $payload['path'] ) );

		readfile( $payload['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Resolve the file to serve for an order, from either the private storage
	 * (Tier 2 meta) or the legacy attachment (Tier 1).
	 *
	 * @param int $order_id Order ID.
	 * @return array|WP_Error Array with `path`, `type` and `name`, or error.
	 */
	private static function resolve_screenshot( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return new WP_Error( 'upiwc_screenshot_not_found', __( 'Screenshot not found.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		$relative = (string) $order->get_meta( self::META_FILE );

		if ( ! empty( $relative ) ) {
			if ( ! self::is_safe_relative_path( $relative ) ) {
				return new WP_Error( 'upiwc_screenshot_not_found', __( 'Screenshot not found.', 'upi-qr-code-payment-for-woocommerce' ) );
			}

			$uploads = wp_get_upload_dir();
			$path    = $uploads['basedir'] . '/' . ltrim( $relative, '/' );

			if ( ! file_exists( $path ) ) {
				return new WP_Error( 'upiwc_screenshot_not_found', __( 'Screenshot not found.', 'upi-qr-code-payment-for-woocommerce' ) );
			}

			return array(
				'path' => $path,
				'type' => preg_match( '/\.png$/i', $path ) ? 'image/png' : 'image/jpeg',
				'name' => basename( $path ),
			);
		}

		// Legacy screenshot (Tier 1): stored as a WordPress attachment.
		$attachment_id = absint( $order->get_meta( self::META_LEGACY_ATT ) );

		if ( empty( $attachment_id ) || 'attachment' !== get_post_type( $attachment_id ) ) {
			return new WP_Error( 'upiwc_screenshot_not_found', __( 'Screenshot not found.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		$path = get_attached_file( $attachment_id );

		if ( empty( $path ) || ! file_exists( $path ) ) {
			return new WP_Error( 'upiwc_screenshot_not_found', __( 'Screenshot not found.', 'upi-qr-code-payment-for-woocommerce' ) );
		}

		$type = 'image/jpeg';

		if ( preg_match( '/\.png$/i', $path ) ) {
			$type = 'image/png';
		}

		return array(
			'path' => $path,
			'type' => $type,
			'name' => basename( $path ),
		);
	}

	/**
	 * Whether a relative path points into the private directory without traversal.
	 *
	 * Used for deletion: permissive about the file name shape, but always
	 * anchored inside `upiwc-private/`.
	 *
	 * @param string $relative Relative path.
	 * @return bool
	 */
	private static function is_private_relative_path( $relative ) {
		$relative = str_replace( '\\', '/', (string) $relative );

		if ( '' === $relative || false !== strpos( $relative, '..' ) ) {
			return false;
		}

		return 1 === preg_match( '#^' . self::PRIVATE_DIR . '/[A-Za-z0-9][A-Za-z0-9/_\-\.]*\.(jpg|jpeg|png)$#i', $relative );
	}

	/**
	 * Reject anything outside the private screenshot directory or containing traversal.
	 *
	 * @param string $relative Relative path.
	 * @return bool
	 */
	private static function is_safe_relative_path( $relative ) {
		$relative = str_replace( '\\', '/', (string) $relative );

		if ( false !== strpos( $relative, '..' ) ) {
			return false;
		}

		// Only accept paths produced by our own storage routine:
		// `upiwc-private/{Y}/{m}/{24 random chars}.{jpg|png}`.
		return 1 === preg_match( '#^' . self::PRIVATE_DIR . '/[0-9]{4}/[0-9]{2}/[a-z0-9]{24}\.(jpg|png)$#i', $relative );
	}

	/**
	 * Tier 1: remove protected legacy screenshots from anonymous REST collections.
	 *
	 * @param array           $args            Query args for WP_Query.
	 * @param WP_REST_Request $request         Current request.
	 * @param object          $rest_controller REST controller instance.
	 * @return array
	 */
	public static function rest_exclude_private_collection( $args, $request, $rest_controller = null ) {
		if ( self::current_user_can_view_screenshots() ) {
			return $args;
		}

		$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one-time NOT EXISTS exclusion for privacy.

		$args['meta_query'][] = array(
			'key'     => self::META_MARK,
			'compare' => 'NOT EXISTS',
		);

		return $args;
	}

	/**
	 * Tier 1: deny individual REST media items for protected legacy screenshots.
	 *
	 * Returns a WP_REST_Response with a 403 status because some WordPress
	 * versions call `$response->get_data()` on the filter result and fail on a
	 * plain WP_Error.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param WP_Post          $post     Attachment post.
	 * @return WP_REST_Response
	 */
	public static function rest_guard_item( $response, $post ) {
		if ( is_object( $post ) && '1' === get_post_meta( $post->ID, self::META_MARK, true ) && ! self::current_user_can_view_screenshots() ) {
			$forbidden = new WP_REST_Response();
			$forbidden->set_data(
				array(
					'code'    => 'rest_forbidden',
					'message' => __( 'Sorry, you are not allowed to view this file.', 'upi-qr-code-payment-for-woocommerce' ),
					'data'    => array( 'status' => 403 ),
				)
			);
			$forbidden->set_status( 403 );

			return $forbidden;
		}

		return $response;
	}

	/**
	 * Tier 1: 404 front-end attachment pages of protected legacy screenshots.
	 *
	 * @return void
	 */
	public static function front_end_guard() {
		if ( is_admin() || ! is_attachment() ) {
			return;
		}

		$attachment_id = (int) get_queried_object_id();

		if ( ! $attachment_id ) {
			return;
		}

		if ( '1' === get_post_meta( $attachment_id, self::META_MARK, true ) && ! self::current_user_can_view_screenshots() ) {
			wp_die(
				esc_html__( 'Not found.', 'upi-qr-code-payment-for-woocommerce' ),
				'',
				array( 'response' => 404 )
			);
		}
	}

	/**
	 * Whether the current user may open payment screenshots.
	 *
	 * @return bool
	 */
	private static function current_user_can_view_screenshots() {
		return current_user_can( 'edit_shop_orders' ); // phpcs:ignore WordPress.WP.Capabilities -- WooCommerce capability, resolved via map_meta_cap when WooCommerce is active.
	}

	/**
	 * Remove a screenshot file when its order is permanently deleted.
	 *
	 * Runs from `woocommerce_before_delete_order`, which WooCommerce fires from
	 * both the classic and HPOS data stores while the order still exists.
	 *
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order object when provided by the data store.
	 * @return void
	 */
	public static function remove_screenshot_for_order( $order_id, $order = null ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! is_a( $order, 'WC_Order' ) || 'wc-upi' !== $order->get_payment_method() ) {
			return;
		}

		// Tier 2: delete the privately stored file. Deletion accepts anything
		// safely under the private directory, so older or differently named
		// files are still removed; serving keeps the strict check below.
		$relative = (string) $order->get_meta( self::META_FILE );

		if ( ! empty( $relative ) && self::is_private_relative_path( $relative ) ) {
			$uploads = wp_get_upload_dir();
			$path    = $uploads['basedir'] . '/' . ltrim( $relative, '/' );

			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}

			self::remove_empty_dirs( dirname( $path ), $uploads['basedir'] . '/' . self::PRIVATE_DIR );
		}

		// Tier 1: delete a legacy attachment when the deleting order is its only
		// owner. Ownership was fully recorded by the one-time routine, so this
		// is a plain meta read - no reference-count queries at delete time.
		$attachment_id = absint( $order->get_meta( self::META_LEGACY_ATT ) );

		if ( $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) ) {
			$owners = array_filter(
				array_map(
					'absint',
					explode( ',', (string) get_post_meta( $attachment_id, self::META_OWNER, true ) )
				)
			);
			$owners = array_values( array_unique( array_diff( $owners, array( (int) $order->get_id() ) ) ) );

			if ( empty( $owners ) ) {
				wp_delete_attachment( $attachment_id, true );
			} else {
				update_post_meta( $attachment_id, self::META_OWNER, implode( ',', $owners ) );
			}
		}

		// Clear the deleted order's screenshot meta.
		$order->delete_meta_data( self::META_FILE );
		$order->delete_meta_data( self::META_LEGACY_ATT );
		$order->save();
	}

	/**
	 * Remove now-empty year/month directories under the private folder.
	 *
	 * @param string $path      Directory to start from.
	 * @param string $stop_root Private root; never removed.
	 * @return void
	 */
	private static function remove_empty_dirs( $path, $stop_root ) {
		$path      = untrailingslashit( (string) $path );
		$stop_root = untrailingslashit( (string) $stop_root );

		while (
			$path
			&& $stop_root
			&& 0 === strpos( str_replace( '\\', '/', $path ), str_replace( '\\', '/', $stop_root ) . '/' )
		) {
			if ( ! @rmdir( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- fails when non-empty, which stops the loop.
				break;
			}

			$path = dirname( $path );
		}
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['upiwc-upi-orders'] = array(
			'exporter_friendly_name' => __( 'UPI Payment Data', 'upi-qr-code-payment-for-woocommerce' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Export UPI payment data for orders of the given email address.
	 *
	 * @param string $email_address Customer email.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		$per_page = 50;
		$orders   = wc_get_orders(
			array(
				'billing_email' => $email_address,
				'limit'         => $per_page,
				'offset'        => ( max( 1, (int) $page ) - 1 ) * $per_page,
			)
		);

		$data = array();

		foreach ( $orders as $order ) {
			if ( ! is_a( $order, 'WC_Order' ) || 'wc-upi' !== $order->get_payment_method() ) {
				continue;
			}

			$items     = array();
			$upi_id    = (string) $order->get_meta( '_transaction_upi_id' );
			$trans_id  = (string) $order->get_transaction_id();

			if ( ! empty( $upi_id ) ) {
				$items[] = array(
					'name'  => __( 'UPI ID', 'upi-qr-code-payment-for-woocommerce' ),
					'value' => $upi_id,
				);
			}

			if ( ! empty( $trans_id ) ) {
				$items[] = array(
					'name'  => __( 'Transaction ID', 'upi-qr-code-payment-for-woocommerce' ),
					'value' => $trans_id,
				);
			}

			$relative = (string) $order->get_meta( self::META_FILE );
			$att_id   = absint( $order->get_meta( self::META_LEGACY_ATT ) );

			if ( ! empty( $relative ) ) {
				$items[] = array(
					'name'  => __( 'Payment screenshot', 'upi-qr-code-payment-for-woocommerce' ),
					'value' => __( 'Stored privately by the store; not publicly downloadable.', 'upi-qr-code-payment-for-woocommerce' ),
				);
			} elseif ( $att_id > 0 ) {
				$url = wp_get_attachment_url( $att_id );

				if ( $url ) {
					$items[] = array(
						'name'  => __( 'Payment screenshot', 'upi-qr-code-payment-for-woocommerce' ),
						'value' => $url,
					);
				}
			}

			if ( ! empty( $items ) ) {
				$data[] = array(
					'group_id'    => 'upiwc_order_' . $order->get_id(),
					'group_label' => sprintf(
						/* translators: %s: order number */
						__( 'Order #%s (UPI payment data)', 'upi-qr-code-payment-for-woocommerce' ),
						$order->get_order_number()
					),
					'items'       => $items,
				);
			}
		}

		return array(
			'data' => $data,
			'done' => count( $orders ) < $per_page,
		);
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['upiwc-upi-orders'] = array(
			'eraser_friendly_name' => __( 'UPI Payment Data', 'upi-qr-code-payment-for-woocommerce' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Erase UPI payment data (UPI ID, transaction ID, screenshot) for the given email.
	 *
	 * @param string $email_address Customer email.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		$per_page = 50;
		$orders   = wc_get_orders(
			array(
				'billing_email' => $email_address,
				'limit'         => $per_page,
				'offset'        => ( max( 1, (int) $page ) - 1 ) * $per_page,
			)
		);

		$items_removed = 0;

		foreach ( $orders as $order ) {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				continue;
			}

			$changed = false;

			if ( $order->get_meta( '_transaction_upi_id' ) !== '' && '' !== (string) $order->get_meta( '_transaction_upi_id' ) ) {
				$order->delete_meta_data( '_transaction_upi_id' );
				$changed = true;
			}

			if ( 'wc-upi' === $order->get_payment_method() ) {
				if ( (string) $order->get_transaction_id() !== '' ) {
					$order->set_transaction_id( '' );
					$changed = true;
				}

				if ( '' !== (string) $order->get_meta( self::META_FILE ) || '' !== (string) $order->get_meta( self::META_LEGACY_ATT ) ) {
					self::remove_screenshot_for_order( $order->get_id(), $order );
					$changed = true;
				}
			}

			if ( $changed ) {
				$order->save();
				++$items_removed;
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $orders ) < $per_page,
		);
	}

	/**
	 * Whether WooCommerce stores order data in the high-performance tables.
	 *
	 * Uses the official `OrderUtil` API. When that utility is absent (WooCommerce
	 * releases that predate HPOS), classic post storage is assumed.
	 *
	 * @return bool
	 */
	private static function is_hpos_in_use() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return false;
		}

		return (bool) \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Tier 1: one-time routine that marks uploads from versions <= 1.6.3.
	 *
	 * Collects attachment IDs referenced in `_upiwc_order_attachment_id` order
	 * meta (classic post meta is always scanned; the HPOS meta table only when
	 * WooCommerce reports that it is in use), marks each attachment and records
	 * which orders own it. No files are moved or deleted.
	 *
	 * The two chunked, `prepare()`-ed scans below are the only low-level reads:
	 * no higher-level API can search order meta across both storage backends
	 * without loading every order object.
	 *
	 * @return void
	 */
	public static function maybe_protect_legacy() {
		if ( 'yes' === get_option( self::OPTION_MIGRATED, 'no' ) ) {
			return;
		}

		global $wpdb;

		$chunk = 5000;
		$refs  = array();

		// Classic post meta storage, always scanned: it holds data of older
		// sites and of sites that enabled HPOS later (synced copies).
		$last_id = 0;

		while ( true ) {
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-time upgrade scan; no API covers this.
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' AND post_id > %d ORDER BY post_id ASC LIMIT %d",
					self::META_LEGACY_ATT,
					$last_id,
					$chunk
				),
				ARRAY_A
			);

			if ( ! is_array( $rows ) || empty( $rows ) ) {
				break;
			}

			$last_id = (int) end( $rows )['post_id'];

			foreach ( $rows as $row ) {
				$attachment_id = absint( $row['meta_value'] );

				if ( $attachment_id > 0 ) {
					$refs[ $attachment_id ][] = (int) $row['post_id'];
				}
			}
		}

		// HPOS meta table, only when WooCommerce reports it is in use.
		if ( self::is_hpos_in_use() ) {
			$hpos_table = $wpdb->prefix . 'wc_orders_meta';
			$last_id    = 0;

			while ( true ) {
				$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-time upgrade scan; no API covers this.
					$wpdb->prepare(
						"SELECT order_id, meta_value FROM {$hpos_table} WHERE meta_key = %s AND meta_value <> '' AND order_id > %d ORDER BY order_id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $hpos_table is derived from $wpdb->prefix, not user input.
						self::META_LEGACY_ATT,
						$last_id,
						$chunk
					),
					ARRAY_A
				);

				if ( ! is_array( $rows ) || empty( $rows ) ) {
					break;
				}

				$last_id = (int) end( $rows )['order_id'];

				foreach ( $rows as $row ) {
					$attachment_id = absint( $row['meta_value'] );

					if ( $attachment_id > 0 ) {
						$refs[ $attachment_id ][] = (int) $row['order_id'];
					}
				}
			}
		}

		foreach ( $refs as $attachment_id => $owners ) {
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			// Reversible: remove this meta and the attachment returns to stock behavior.
			update_post_meta( $attachment_id, self::META_MARK, '1' );

			// Record every referencing order; deletion later removes an order
			// from this list instead of running reference-count queries.
			$claimed  = (string) get_post_meta( $attachment_id, self::META_OWNER, true );
			$existing = '' !== $claimed ? array_filter( array_map( 'absint', explode( ',', $claimed ) ) ) : array();
			$owners   = array_values( array_unique( array_merge( $existing, $owners ) ) );

			update_post_meta( $attachment_id, self::META_OWNER, implode( ',', $owners ) );
		}

		update_option( self::OPTION_MIGRATED, 'yes', false );
	}
}