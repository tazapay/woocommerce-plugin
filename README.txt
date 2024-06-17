=== Tazapay Checkout Payment Gateway ===
Contributors: tazapay
Donate link: https://tazapay.com/
Tags: TazaPay, WooCommerce, credit card, gateway
Requires at least: 4.0
Tested up to: 6.3
Stable tag: 3.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin enables your WooCommerce powered platform to start accepting international payments via Tazapay's Escrow product.

== Description ==

<strong>Why Tazapay?</strong>
<ul>
<li>Over 173 markets and 70+ localised payment methods</li>
<li>1.8% payment fees for non-card payments</li>
<li>Enable buyer protection for high-value transactions</li>
</ul>

<strong>How does Tazapay checkout work?</strong>

Choose between allowing direct payments or escrow payments when handling transactions on your online shop.

Direct Payment (Ideal for High Volume, Low Value Purchases)
<ol>
<li>
Once your buyer confirms their order and chooses Tazapay checkout, they will be redirected to Tazapay’s checkout page where they can select between multiple payment methods ranging from:
<ul>
<li>Bank transfer</li>
<li>QR code</li>
<li>Voucher payments</li>
<li>Cards and more</li>
</ul>
Available payment methods depend on where they are located.</li>

<li>Upon payment receipt, the seller can opt to choose between getting their payout in USD or with their local currency.</li>
</ol>

Escrow Payment (Ideal for High Value, Low Volume Purchases)
<ol>
<li>The buyer and seller agree to the terms of trade, and the buyer first pays to the escrow account in an online checkout</li>
<li>Once the payment is complete, the funds are received and secured in a bank account under the jurisdiction of MAS (Monetary Authority of Singapore).</li>
<li>When the product is shipped or the services are rendered, the seller (or your platform) can provide a proof of fulfilment to Tazapay for verification</li>
<li>As soon as Tazapay verifies the documents, the payment is released to the seller</li>
</ol>

<strong>Features</strong>
<ul>
<li>Enable cost-effective localised payment methods to your buyers at more than 70 major markets</li>
<li>Checkout paywall and payment methods are dynamically loaded based on buyer’s location</li>
<li>Transparent and competitive FX rates displayed upfront for all payment methods during checkout</li>
<li>Offer protection for high-value transactions with Tazapay’s escrow & give your buyers a peace of mind</li>
</ul>

<strong>How to Install Tazapay’s WooCommerce App?</strong>

Read our step-by-step guide on our FAQ, <a href="https://support.tazapay.com/how-do-i-install-tazapays-woocommerce-plugin" target="_blank">how do I install Tazapay’s WooCommerce Plugin</a>.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tazapay-checkout-payment-gateway` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= How to get your credentials? =

https://app.tazapay.com/signup


== Screenshots ==

1. Admin Settings Page
2. Admin Settings Page
3. Woocommerce Checkout Page
4. Customisable Payment UI
5. Customisable Payment UI
6. Customisable Payment Completion Screen
7. Order thank you page

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
- Speed optimization by storing seller info in database
ver 1.3.5:
- Plugin description updated
ver 1.3.6:
- Plugin description updated
ver 1.3.7:
- Plugin description updated
ver 1.3.8:
- Order status update on offline payment approval
ver 1.3.9:
- Bug fixing to handle critical error log
ver 1.4.0:
- Api optimization and handle api errors
ver 1.4.1:
- code optimization
ver 1.4.2:
- Tested for 6.1.1 and PHP_CodeSniffer bug fixes
ver 1.4.3:
- code Sanitized
ver 1.4.4:
- Updated Unset sessions
ver 1.4.5:
- Passed Order currency as invoice instead of WooCommerce currency
ver 1.4.6:
- Handled error validation and optimization of code done.
ver 1.4.7:
- Handle redirection to checkout page.
ver 1.4.8:
- Handle payment status change.
ver 1.4.9:
- Handle payment status change to processing.
ver 1.5.0:
- Handled Fee Paid by Buyer.
ver 1.5.1:
- Handled hook trigger for order status change.
ver 1.5.2:
- Fix order status change to processing.
ver 2.0.0:
- Enhanced user experience for payment.
ver 2.0.1:
- Skipping status update in webhook for release authorized and payout completed statuses
ver 2.0.2:
- Skipping status update for pre-order items and avoiding multiple txns for same order id.
ver 2.0.3:
- Rewamp to support new platform.
ver 3.0:
- Rewamp to support new platform and bug fixes.
ver 3.0.1:
- Rewamp to support Tbridge payment options like (UPI,NetBanking and cards).
== Upgrade Notice ==

