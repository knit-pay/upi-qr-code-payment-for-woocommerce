=== UPI QR Code Payment for WooCommerce ===
Contributors: infosatech
Tags: upi, upi payment, woocommerce, qrcode, bhim upi, paytm upi, india
Requires at least: 4.6
Tested up to: 5.5
Stable tag: 1.1.6
Requires PHP: 5.6
Donate link: https://www.paypal.me/iamsayan/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

This Plugin enables WooCommerce shopowners to get direct and instant payments through UPI apps like BHIM, Google Pay, Whatsapp, Paytm, PhonePe or any banking UPI app to save payment gateway charges in India.

== Description ==

This Plugin enables WooCommerce shopowners to get direct and instant payments through UPI apps like BHIM, Google Pay, Whatsapp, Paytm, PhonePe or any banking UPI app to save payment gateway charges in India.

### UPI QR Code Payment for WooCommerce

UPI (Unified Payments Interface) is a payment standard owned by National Payment Corporation of India, a government owned instant payment solution. UPI works 24x7 and is free subject to prevalent government guidelines.

When this plugin is installed, a customer will see UPI as a payment option. When customer chooses it, it will open a page which shows the UPI QR Code containg the payemnt details and in mobile it will also show a button which takes customer to the list of installed UPI mobile applications. Customer can choose an app and pay the required amount. 

Like UPI QR Code Payment for WooCommerce plugin? Consider leaving a [5 star review](https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/reviews/?rate=5#new-post).

#### Benefits

* Simple & Easy to Setup.
* Avoid Payment Gateway Fees.
* Instant Settlement.
* Direct Payment.
* 100% Success Rate.
* Send QR Code link to Customer.
* 24x7 Availibilty.
* Multisite Network Supproted.
* No Renewal/Subscription.
* No KYC, No GST number Required.
* No Hidden or Additional Charges.
* Instant Money Settlement.

#### Detailed Steps

* Customer will see UPI as a payment option in WooCommerce Checkout page.
* When customer chooses it, it will open a page which shows the UPI QR Code containg the payemnt details and in mobile it will also show a button which takes customer to the list of installed UPI mobile applications.
* Customer can scan the QR Code using any UPI app or choose an app from mobile to pay the required order amount.
* After successful payment, a 12-digits Transaction/UTR ID will apprear in the Customer's UPI app from which he/she made the payment.
* After that, customer needs to enter that 12 digit transaction number to the "Enter the Transaction ID" textbox and click submit.
* After successful submission of the ID, the order will be marked as on hold (customizable).
* Now, Merchant gets a notification on the mobile on his/her UPI app (Google Pay/PhonePe/BHIM/Paytm etc.)
* Merchant opens notification, sees a payment made. Sees the "Order ID".
* Merchant opens the Woocommerce Dashboard, checks the "pending orders" for this Order ID.
* Checks the order details and processes it (shipping etc) and makes the orders as "processing" or "completed".

#### Compatibility

* This plugin is fully compatible with WordPress Version 4.6 and beyond and also compatible with any WordPress theme.

#### Support
* Community support via the [support forums](https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce) at WordPress.org.

#### Contribute
* Active development of this plugin is handled [on GitHub](https://github.com/iamsayan/upi-qr-code-payment-for-woocommerce).
* Feel free to [fork the project on GitHub](https://github.com/iamsayan/upi-qr-code-payment-for-woocommerce) and submit your contributions via pull request.

== Installation ==

1. Visit 'Plugins > Add New'.
1. Search for 'UPI QR Code Payment for WooCommerce' and install it.
1. Or you can upload the `upi-qr-code-payment-for-woocommerce` folder to the `/wp-content/plugins/` directory manually.
1. Activate UPI QR Code Payment for WooCommerce from your Plugins page.
1. After activation go to 'WooCommerce > Settings > Payments > UPI QR Code'.
1. Enable options and save changes.

== Frequently Asked Questions ==

= Is there any admin interface for this plugin? =

Yes. You can access this from 'WooCommerce > Settings > Payments > UPI QR Code'.

= How to use this plugin? =

Go to 'WooCommerce > Settings > Payments > UPI QR Code', enable/disable options as per your need and save your changes.

= Is this plugin compatible with any themes? =

Yes, this plugin is compatible with any theme. Also, compatible with Genesis, Divi themes.

= I want auto verification after payment is done. Is is possible? =

Unfortunately no, automatic payment verification is not possible as NPCI does not allow to use their API and verify the traansaction of any website.

= The plugin isn't working or have a bug? =

Post detailed information about the issue in the [support forum](https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce) and I will work to fix it.

== Screenshots ==

1. Admin Dashboard
2. Checkout page
3. QR Code Page
4. Qr Code Verification Message
5. Order Received/thank you page.
6. Order Details

== Changelog ==

If you like UPI QR Code Payment for WooCommerce, please take a moment to [give a 5-star rating](https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/reviews/?rate=5#new-post). It helps to keep development and support going strong. Thank you!

= 1.1.6 =
Release Date: September 18, 2020

* Added: An option on payment page to go back to select any mayment method. This can be disabled by a filter.
* Tweak: UPI Pay Button will be shown only on android devices.
* Tweak: Added some CSS Improvements.
* Optimize codes and stability.
* Tested with WooCommerce v4.5.

= 1.1.5 =
Release Date: August 14, 2020

* Fixed: Popup is not showing if QR is hidden on mobile devices.
* Tested with WordPress v5.5.

= 1.1.4 =
Release Date: June 29, 2020

* Fixed: Some JS issues.

= 1.1.3 =
Release Date: June 24, 2020

* Added: Option to get Transaction ID, which was previously removed on v1.1.2.
* Added: Option to change payment confirm message.
* Added: Some validations.
* Tweak: Sort UPI Handles alphabatically and introduced autocomplete.
* Fixed: UPI ID is not showing as WooCommerce transaction ID.
* Fixed: Some CSS & JS issues.
* Other Improvements.

= 1.1.2 =
Release Date: June 9, 2020

* Added: Dark Mode for popup.
* Tweak: Plugin will now show UPI ID field on checkout page as it will boost customer experience on checkout instead of getting the UPI ID after the payment. It can be disable ussing this filter: `upiwc_is_upi_enable`.
* Tweak: Transaction ID is completely removed to simplify the customer checkout experience.
* Tweak: Added some styles on order payment page to enhance the checkout experience.
* Tweak: Cancel button will redirect customers to checkout page.
* Tweak: Cart will be automatically cleared if payment is actually completed.
* Fixed: 404 not found issue and other permaalinks issues.
* WC Compatibity upto v4.2.

= 1.1.1 =
Release Date: June 1, 2020

* Tweak: Added some css classes to use custom styling for mobile button.
* Fixed: A bug where 404 error is thrown if WordPress does not use pretty permalinks.
* Fixed: A bug where popup becomes unresponsive in mobile devices if QR Code is hidden on mobile device.
* Fixed: Some CSS & JS issues.

= 1.1.0 =
Release Date: May 24, 2020

* Tweak: Can disable Payment details collection from plugin settings.
* Fixed: Some CSS & JS issues.
* Fixed: Custom button text is not working.

= 1.0.9 =
Release Date: May 20, 2020

* Tweak: Payment Dialog will automatically apprear after page load.
* Fixed: The `upiwc_order_total_amount` filter is not working.

= 1.0.8 =
Release Date: May 18, 2020

* Fixed: A PHP error.
* Fixed: Wrong tooltips.
* Updated the Payment gateway logo.

= 1.0.7 =
Release Date: May 18, 2020

* NEW: Added option to enable or disable UPI Handle field.
* NEW: Added option to enable or disable UPI Transaction field.
* NEW: Added option to make UPI Transaction field required at the time of checkout.
* NEW: Added option to hide QR Code on mobile devices.
* NEW: Added a cancel button to go back to the actual order pay page if customer wants to change payment method.
* Improved: Payment verification process to prevent false order payment.
* Improved: Added some mobile only CSS to make it easy for customers.
* Tweak: Make some settings JS based to make the checkout process smooth.
* Tweak: It is now possible to use any other payment method to make payment when payment initialized at first through the UPI QR Code method.
* Fixed: Undefined variable issue which shows warning at the time of processing payment.
* Fixed: Some CSS & JS issues.
* Fixed: Untraslated strings.
* Cleanup: Removed unnecessary codes.
* Compatibity with WooCommerce v4.1.

= 1.0.6 =
Release Date: April 28, 2020

* Added: A filter to disable UPI Handle field.
* Fixed: Some CSS issues.
* Fixed: Untraslated strings.

= 1.0.5 =
Release Date: April 26, 2020

* Added: Client Side validation for UPI ID and Transaction ID.
* Tweak: Applied QR Auto corrction and resizes it to 150x150 px.
* Fixed: QR Code is not displaying if there is some unexpected charecters.
* Fixed: Some typos.
* Fixed: Untraslated strings.

= 1.0.4 =
Release Date: April 15, 2020

* Improved: Order Confirm mechanism.
* Fixed: Dialog Box issue on Mobile devices.

= 1.0.3 =
Release Date: April 13, 2020

* NEW: Major UI Changes in Payment Confirm Process.
* Fixed: A bug where payment can't be done using BHIM App.

= 1.0.2 =
Release Date: March 20, 2020

* Fixed: A bug where plugin shows a false 403 error.

= 1.0.1 =
Release Date: March 14, 2020

* Added: JS Validation to verify the UPI Transaction ID.
* Added: A filter `upiwc_capture_payment_redirect_notice` to customize the payment success message.
* Improved: Now this plugin treats all unpaid orders as pending. After successful UPI Validation order status will be changed into "On Hold". It can be customized from plugin settings.
* Improved: This plugin now uses On Hold Order Email template to send payment pending email to customers.
* Fixed: Some CSS Errors.
* Fixed: Some typo.
* Tested upto WordPress v5.4 and WooCommerce v4.0.

= 1.0.0 =
Release Date: January 30, 2020

* Initial release.

== Upgrade Notice ==