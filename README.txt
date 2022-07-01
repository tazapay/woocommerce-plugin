=== Tazapay Checkout Payment Gateway ===
Contributors: tazapay
Donate link: https://tazapay.com/
Tags: TazaPay, WooCommerce, credit card, gateway
Requires at least: 4.0
Tested up to: 6.0
Stable tag: 1.3.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin enables your WooCommerce powered platform to start accepting international payments via Tazapay's Escrow product.

== Description ==

* How do Tazapay's escrow payments work ?
1. The buyer can select the product or service and make a payment like any other online checkout option (available payment methods depend on the buyer's country and the amount of money to be transferred)
2. Once the payment is complete, the funds are received and secured in a bank account under the jurisdiction of MAS (Monetary Authority of Singapore)
3. Once the product is shipped or the services rendered, the seller (or your platform) can provide a proof of order fulfillment to Tazapay for verification
4. As soon as Tazapay verifies the documents, the payment is released to the seller

* Features
1. Add an international payment method to your checkout page to enable payments from buyers from over 90+ countries
2. Low cost secured payments for buyers and sellers at best in class FX rates
3. Easily monetize your platform by enabling a platform fee: we handle the collection and settlement on your behalf!
4. Wide variety of payment methods accepted: Mastercard, VISA, Local Bank Transfers, and other local payment
5. Especially relevant for B2B as large value transactions upto $1M are supported at a low cost and with escrow protection. Fully compliant with local and international regulations, all relevant trade documents are provided.

* Get Started with Tazapay Payments Plugin
1. Request your API Key and Secret by signing up here: 
   Sandbox: https://sandbox.tazapay.com/signup
   Production: https://app.tazapay.com/signup
2. Install WooCommerce and activate your plugin
3. Download the Tazapay payment module from: https://wordpress.org/plugins/wc-tp-payment-gateway/
4. Go to the 'Admin Panel' and upload the zipped file you downloaded in the 'Plugins' option and activate
5. Go to the default WooCommerce settings menu and click on the 'Payments' tab 
6. Enable Tazapay Payments Plugin
7. Add your 'API Key' and 'Secret' (obtained from Tazapay after completign Step 1) in the Tazapay Payments Plugin management in the default WooCommerce payment tab (NOTE: You can add 'sandbox' keys for test transactions and 'production' keys for real transactions)
8. Please input the email ID which you used to signup with Tazapay
9. Please ensure that "TazaPay Users" is checked (child of WooCommerce menu)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tazapay-checkout-payment-gateway` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= How to get your credentials? =

https://app.tazapay.com/signup


== Screenshots ==

1. Settings page
2. Checkout page
3. Redirect to sandbox
4. Local bank transfer
5. Order thank you page

== Changelog ==
ver 1.1.3:  
- Escrow initiatiated by Seller ID (bug fix) 
ver 1.2.1: 
- Seller email and api key Validation at WooCommerce Tazapay settings 
- Payment logo update
ver 1.2.2:
- Now the value of transaction_source can also be passed 
ver 1.3.0:
- Refund module integrated 
ver 1.3.1:
- Refund module text changes 
ver 1.3.3:
- Bug Fix > Refund API slowing down site 
ver 1.3.4:
  Speed optimization by storing seller info in database

== Upgrade Notice ==

