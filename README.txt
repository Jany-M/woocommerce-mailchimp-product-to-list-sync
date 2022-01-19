=== WooCommerce: Sync Product's Customer to Mailchimp Audience ===

Contributors: Shambix, Dukessa
Author URL: https://www.shambix.com
Tags: woocommerce, mailchimp, mailchimp api, mailchimp sync, woocommerce sync, mailchimp audience, mailchimp lists
Requires at least: 5
Tested up to: 5.8.3
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Assign WooCommerce products to Mailchimp Audiences (old Lists) and sync customers to them upon completed purchase.

== Description ==

This plugin allows you to create or assign a Mailchimp Audience (old List) to a product.

Once a customer purchases the product and payment is complete (either through online payment eg. Paypal/Stripe or by admin setting order as completed), they will be automatically synced to that product's assigned audience.

In case the Mailchimp Audience gets deleted from the Mailchimp dashboard, in the Woocommerce order page backend, it will show as Audience not assigned anymore, so admin can re-assign one to the product, or create a new one. Admin will then be able to force sync directly from the WooCommerce order backend and check if user is already synced.

You can leave the Audience name empty, in the Product edit page, in that case the Audience name will be created automatically following the format `SKU-slug-of-product`.

To save the assigned or new Audience to the product, just publish the product itself.

**Remember to add your Mailchimp API key and Audience default information, in the plugin's options, or the plugin won't work.**

You can also check and export to csv, the plugin's activity log, from the its options page, in case of sync errors (this meant for developers) and also how many Audiences you currently have on your Mailchimp account as well as the number of subscribers in each.

**CURRENTLY this plugin does NOT ask confirmation to the customer, to be synced to the Mailchimp Audience, so the customer has no way of opting-out or refusing prior to be subscribed, so please check your Country's privacy regulations before using it and act accordingly: the responsibility to inform the customer in compliance to the applicable laws, and/or add custom code to allow the user to opt-out, is yours and yours only.**

== Requirements ==

* [WooCommerce](https://wordpress.org/plugins/woocommerce/), installed and active.
* [A Mailchimp account](https://mailchimp.com/) that allows you to have multiple Audiences, unless you want to use a single one for all products.
* [A Mailchimp API key](https://mailchimp.com/help/about-api-keys/).

== Questions? ==

Check the FAQ before opening new threads in the forum!

> Contact me if you want a **custom version of the plugin**, for a fee (contact form at [shambix.com](https://www.shambix.com)).

* [Github repo](https://github.com/Jany-M/woocommerce-mailchimp-product-to-list-sync)

== Installation ==

1. Upload `woocommerce-mailchimp-product-to-list-sync.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'WooCommerce' menu -> 'Mailchimp Product sync' submenu, to access the plugins settings and log
4. Add your Mailchimp API to the plugin's options, as well as the default values for new Audiences creation, or the plugin won't work/sync anything

== Frequently Asked Questions ==

= Can I assign more than one Audience to a single product? =

Nope.

= Can I sync to more than one Mailchimp account? =

Nope.

= How do I save the Audience to the product? =

Go to the product edit page, in the WordPress backend, assign an existing Audience or create a new one, then simply save the product itself (publish it).

= I deleted/archived asubscriber from an assigned Audience from Mailchimp by mistake! =

You can check the meaning of each Audience default value, inside the plugin's options (WooCommerce -> Mailchimp Product sync), from the official [Mailchimp API documentation](https://mailchimp.com/developer/marketing/api/lists/add-list/).

= I deleted an assigned Audience from Mailchimp by mistake! =

Just go back to the Product edit page and assign a new or existing list.

Then go in your WooCommerce Order list and for all orders that contained that product, make sure you get into each one and force the re-sync of the customer.

= I deleted/archived asubscriber from an assigned Audience from Mailchimp by mistake! =

Go in your WooCommerce Order list and find the Order from that particular customer.
Now you can force the re-sync of the customer to that assigned Audience.

= Something isn't working! =

You want to make sure you added your Mailchimp API key correctly, to the plugin's option page.

Then please have a look at the plugin's activity log and show it to a developer. It will contain everything they need to know to help you out.

You can also export the log to csv and change the csv delimiter to another symbol (default is ,).

Unfortunately I cannot provide timely individual support, for lack of time (this is a free plugin, thanks for understanding). You can open a new topic in the support forum, I'll do my best to help, whenever I have some spare time.

== Screenshots ==

1. WooCommerce backend Product page - Mailchimp Metabox to assign or create an Audience for the product
2. WooCommerce backend Order page - Customer sync status to assigned Audience (customer has been subscribed to Audience)
3. WooCommerce backend Order page - Assigned Audience doesn't exist anymore on Mailchimp (probably deleted manually)

== Changelog ==

= 1.0.1-beta =
* Initial release of plugin
