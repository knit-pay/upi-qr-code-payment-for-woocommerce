# Changelog
All notable changes to this project will be documented in this file.

## 1.0.4
Release Date: April 15, 2020

* Improved: Order Confirm mechanism.
* Fixed: Dialog Box issue on Mobile devices.

## 1.0.3
Release Date: April 13, 2020

* NEW: Major UI Changes in Payment Confirm Process.
* Fixed: A bug where payment can't be done using BHIM App.

## 1.0.2
Release Date: March 20, 2020

* Fixed: A bug where plugin shows a false 403 error.

## 1.0.1
Release Date: March 14, 2020

* Added: JS Validation to verify the UPI Transaction ID.
* Added: A filter `upiwc_capture_payment_redirect_notice` to customize the payment success message.
* Improved: Now this plugin treats all unpaid orders as pending. After successful UPI Validation order status will be changed into "On Hold". It can be customized from plugin settings.
* Improved: This plugin now uses On Hold Order Email template to send payment pending email to customers.
* Fixed: Some CSS Errors.
* Fixed: Some typo.
* Tested upto WordPress v5.4 and WooCommerce v4.0.

## 1.0.0
Release Date: January 30, 2020

* Initial release.