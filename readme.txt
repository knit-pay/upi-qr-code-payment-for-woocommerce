=== UPI QR Code Payment Gateway for WooCommerce ===
Contributors: infosatech
Tags: upi, upi payment, woocommerce, qrcode, bhim upi, paytm upi, india
Requires at least: 4.6
Tested up to: 6.4
Stable tag: 1.4.2
Requires PHP: 5.6
Donate link: https://www.sayandatta.co.in/donate
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

This Plugin enables WooCommerce shop owners to get direct and instant payments through UPI apps like BHIM, GooglePay, WhatsApp, Paytm, PhonePe or any banking UPI app to save payment gateway charges in India.

== Description ==

This Plugin enables WooCommerce shop owners to get direct and instant payments through UPI apps like BHIM, GooglePay, WhatsApp, Paytm, PhonePe or any banking UPI app to save payment gateway charges in India.

### UPI QR Code Payment Gateway for WooCommerce

UPI (Unified Payments Interface) is a payment standard owned by National Payment Corporation of India, a government owned instant payment solution. UPI works 24x7 and is free subject to prevalent government guidelines.

When this plugin is installed, a customer will see UPI as a payment option. When customer chooses it, it will open a page which shows the UPI QR Code containing the payment details and in mobile it will also show a button which takes customer to the list of installed UPI mobile applications. Customer can choose an app and pay the required amount. 

Like UPI QR Code Payment Gateway for WooCommerce plugin? Consider leaving a [5 star review](https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/reviews/?rate=5#new-post).

#### Benefits

* Simple & Easy to Setup.
* Avoid Payment Gateway Fees.
* Instant Settlement.
* Direct Payment.
* 100% Success Rate.
* Send QR Code link to Customer.
* 24x7 Availability.
* Multisite Network Supported.
* No Renewal/Subscription.
* No KYC, No GST number Required.
* No Hidden or Additional Charges.
* Instant Money Settlement.

#### Detailed Steps

* Customer will see UPI as a payment option in WooCommerce Checkout page.
* When customer chooses it, it will open a page which shows the UPI QR Code containing the payment details and in mobile it will also show a button which takes customer to the list of installed UPI mobile applications.
* Customer can scan the QR Code using any UPI app or choose an app from mobile to pay the required order amount.
* After successful payment, a 12-digits Transaction/UTR ID will appear in the Customer's UPI app from which he/she made the payment.
* After that, customer needs to enter that 12 digit transaction number to the "Enter the Transaction ID" text box and click submit.
* After successful submission of the ID, the order will be marked as on hold (customizable).
* Now, Merchant gets a notification on the mobile on his/her UPI app (Google Pay/PhonePe/BHIM/Paytm etc.)
* Merchant opens notification, sees a payment made. Sees the "Order ID".
* Merchant opens the WooCommerce Dashboard, checks the "pending orders" for this Order ID.
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
1. Search for 'UPI QR Code Payment Gateway for WooCommerce' and install it.
1. Or you can upload the `upi-qr-code-payment-for-woocommerce` folder to the `/wp-content/plugins/` directory manually.
1. Activate UPI QR Code Payment Gateway for WooCommerce from your Plugins page.
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

Unfortunately no, automatic payment verification is not possible as NPCI does not allow to use their API and verify the transaction of any external website.

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

If you like UPI QR Code Payment Gateway for WooCommerce, please take a moment to [give a 5-star rating](https://wordpress.org/support/plugin/upi-qr-code-payment-for-woocommerce/reviews/?rate=5#new-post). It helps to keep development and support going strong. Thank you!

= 1.4.2 =
Release Date: March 3, 2024

* Fixed: Form not submitting if both fields are hidden.

= 1.4.1 =
Release Date: January 7, 2024

* Fixed: CSS issues.
* Tweak: PHP 8.3 Support.

= 1.4.0 =
Release Date: January 6, 2024

* Added: Support for WooCommerce Block-based checkout.

= 1.3.9 =
Release Date: January 6, 2024

* Added: Security check on submission.
* Removed: Inter as default font.

= 1.3.8 =
Release Date: January 5, 2024

* Added: Screenshot upload field.
* Fixed: CSS was not loading on checkout page after last update.

= 1.3.7 =
Release Date: January 4, 2024

* Added: Inter as default font family for payment modal.
* Fixed: Payment Modal not showing on some installations.
* Tweak: Added check for WooCommerce Order object.
* Tweak: Added CSS fixes.
* Tested with WooCommerce v8.6.

= 1.3.6 =
Release Date: July 22, 2023

* Fixed: Persistent admin notices.
* Tweak: Added CSS fixes.
* Tested with WooCommerce v7.9.

= 1.3.5 =
Release Date: June 11, 2023

* Fixed: Thank you note was not visible on some themes.
* Fixed: CSS issues on some themes.
* Added: `upiwc_order_button_text` filter to change order button text on checkout page.
* Tweak: `upiwc_custom_gateway_icon` filter to `upiwc_gateway_icon`. It can be used to change payment gateway icon.
* Tweak: CSS and JS will only load if the UPI ID option is enabled on checkout page.

= 1.3.4 =
Release Date: June 9, 2023

* Added: High-Performance Order Storage support.
* Added: Some JS actions.
* Fixed: Label color in dark mode.
* Tested with WooCommerce v7.8.

= 1.3.3 =
Release Date: March 27, 2023

* Fixed: Proceed to next step button is not visible on some cases.

= 1.3.2 =
Release Date: March 25, 2023

* Added: Theme Options.
* Tweak: Added CSS fixes.

= 1.3.1 =
Release Date: March 17, 2023

* Fixed: Tap to Pay button was not working after last update.
* Fixed: Confirm button was not working after last update if the transaction id field is hidden.
* Tweak: Popup Display on Mobile breakpoints.
* Added: Option to Enable / Disable auto launch UPI Apps.

= 1.3.0 =
Release Date: March 16, 2023

* NEW: Payment UI Interface.
* Added: Settings Categorization.
* Tweak: Various Improvements and fixes.
* Tested with WooCommerce v7.5.

= 1.2.5 =
Release Date: February 16, 2023

* Fixed: UTR ID validation on some installations.
* Tested with WooCommerce v7.4.

= 1.2.4 =
Release Date: January 31, 2023

* Added: Payment details in order admin column.
* Tweak: Strengthened the UTR ID validation.
* Tweak: UPI Transaction ID field will be now default for new installations.
* Fixed: Wrong texts and typos.
* Tested with WordPress v6.1 and WooCommerce v7.3.

= 1.2.3 =
Release Date: June 4, 2022

* Added: Merchant Category Code input option.
* Tested with WordPress v6.0 and WooCommerce v6.5.

= 1.2.2 =
Release Date: June 16, 2021

* Removed: Mobile Phone Logic.
* Tested with WooCommerce v5.4.

= 1.2.1 =
Release Date: May 24, 2021

* Added: A delay of 30 seconds on 'Proceed to Next Button'.
* Added: Merchant code so that you don't need to add it.
* Added: New Payment Icon.
* Tested with WooCommerce v5.3.

= 1.2.0 =
Release Date: March 16, 2021

* Added: Option to add Merchant Codes according to the latest UPI Specification. Please use a valid Merchant UPI VPA ID (not user UPI ID), otherwise all payments will be failed.
* Added: A button to download the QR Codes on Mobile Devices easily.
* Tweak: Hide Copy UPI ID button by default. It can enabled via this filter: `add_filter( 'upiwc_show_upi_id_copy_button', '__return_true' );`.
* Tested with WordPress v5.7 and WooCommerce v5.1.

= 1.1.10 =
Release Date: February 5, 2021

* Removed: Trademark violations.
* Tested with WooCommerce v5.0.

= 1.1.9 =
Release Date: January 16, 2021

* Added: An button to copy the merchant's UPI ID so that customer can easily copy the ID and goes to any UPI App to make the payment.
* Added: An option to set custom payment instructions for mobile devices.
* Added: Filter to show direct UPI pay button. If you want this, add this line to the end of your theme's functions.php file: `add_filter( 'upiwc_show_direct_pay_button', '__return_true' );`.
* Tweak: Customer UPI ID will be shown on popup if it is actually entered by the customer on checkout page.
* Tested with WooCommerce v4.9.

= 1.1.8 =
Release Date: January 12, 2021

* Tweak: According to the recents changes in National Payments Corporation of India(NPCI) direct UPI Intent generated by any web app is not supported anymore. So, from now this plugin only works with UPI QR code and on mobile devices this payment gateway will be hidden by default.
* Tested with WordPress v5.6.

= 1.1.7 =
Release Date: December 7, 2020

* Added: A method by which UPI apps will be launched automatically on Android devices.
* Added: Customer's UPI ID in Payment Popup page.
* Fixed: UPI Payment button is not showing on mobile devices.
* Tweak: UPI QR Code will be shown everytime on iOS devices.
* Tweak: UPI Pay Button will be shown only on android devices.
* Tweak: A 90 seconds timer to look more professional.
* Tweak: Added some CSS Improvements.
* Removed: Country restrictions.
* Optimize codes and stability.
* Tested with WooCommerce v4.7.

= 1.1.6 =
Release Date: September 18, 2020

* Added: An option on payment page to go back to select any payment method. This can be disabled by a filter.
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
* WC Compatibity up to v4.2.

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
* Tested up to WordPress v5.4 and WooCommerce v4.0.

= 1.0.0 =
Release Date: January 30, 2020

* Initial release.