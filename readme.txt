=== Uqpay Payment Gateway ===
Contributors: jemmyzheng
Donate link: https://www.uqpay.com/
Tags: qr payment, credit card, uqpay, union pay, apple pay, payment request, google pay, sepa, sofort, bancontact, alipay, giropay, ideal, p24, woocommerce, automattic
Requires at least: 4.4
Tested up to: 5.2.1
Stable tag: 1.2.1
Requires PHP: 5.6
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Let your woocommerce support mainstream payment channels by UQPAY

== Description ==

This plugin will let you use UQPAY Server with woocommerce.

For 1.x version, the plugin will support:

* Credit Card
* Union Pay Online
* Union Pay Online QR
* AliPay Online QR
* Wechat Pay Online QR

For more business needs, you can consult salesman of [uqpay](https://www.uqpay.com/company/contact.html).

== Installation ==

Please note, the plugin requires WooCommerce 2.6 and above.

Before use this plugin, your need [register](https://merchant.uqpay.com#register) as an UQPAY merchant.

= Automatic installation =

Install the plugin through the WordPress plugins screen directly

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Does this support recurring payments, like for subscriptions? =

No! But coming soon

= Does this require an SSL certificate? =

Yes! In Live Mode, an SSL certificate must be installed on your site

= Does this support both production mode and sandbox mode for testing? =

Yes, it does - production and sandbox mode is driven by the API keys you use.

== Changelog ==

= 1.2.1 - 2020-04-21 =
* fix: dont check login when request plugin api

= 1.2.0 - 2020-04-21 =
* feat: supported Alipay Online QR
* feat: supported Wechat Online QR
* fix: update uqpay php library
* refactor: send payment request without using the original order id to support multiple payments for the same order (if the last payment can't complete)

= 1.1.0 - 2019-12-27 =
* feat: supported UnionPay Online QR

= 1.0.3 - 2019-09-11 =
* refactor: use uqpay php library

= 1.0.2 - 2019-09-04 =
* fix: syntax error

= 1.0.0 - 2019-08-23 =
* feat: supported UnionPay Online
* feat: supported refund

== Upgrade Notice ==

Need to reconfigure, add general configuration, and independent configuration for each payment method.
