=== Airalo ===
Contributors: amaaloufairalo, isikiyski, mhraza123, fatihtzn
Tags: esim, woocommerce
Requires at least: 6.5.3
Tested up to: 6.6.2
Stable tag: 1.0.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

The Airalo plugin allows you to seamlessly sync our products with your store.

== Description ==

The Airalo Plugin for eSIM Integration is designed to seamlessly sync eSIM packages from our platform to your WooCommerce store and handle order submissions automatically. This plugin simplifies the management of eSIM products and ensures smooth communication between your WooCommerce store and our backend services.

== Features ==

- **Product Synchronization**: Automatically sync Airalo eSIM packages from our platform to your WooCommerce store, keeping your product listings up to date.
- **Order Submission**: Once an order is paid, the plugin submits the order details to our Partner REST API endpoint, ensuring efficient processing and fulfilment.
- **Easy Configuration**: Setup credentials and configure product sync options within the WooCommerce admin panel, making the integration process straightforward and user-friendly.

== Account Creation ==

- We'll be onboarding you onto the Airalo Partner Platform to provide you with access to the API credentials needed for the Airalo WooCommerce plugin.
- Once your company is created, you'll receive an email to set up your password. This will enable you to sign in to the Airalo Partner Platform and securely retrieve your API credentials.
- Not a partner yet? sign up here: https://partners.airalo.com/sign-up

== Installation ==

- Navigate to the plugin section in your admin dashboard
- Click on "Add New Plugin"
- Search for "Airalo"
- Click the "Install Now" button
- Navigate to the "Installed Plugins Page"
- Click on "Activate"

== Usage ==

- **Install the Plugin**: Install the plugin from the Wordpress Plugin Store.
- **Configure Credentials**: Navigate to the plugin settings and enter your Airalo Partner API credentials to establish a connection with our platform.
- **Configure Product Sync Options**: Set up synchronization preferences to control how Airalo eSIM packages are imported and displayed in your WooCommerce store.
- **Automatic Order Handling**: The plugin will automatically handle order submissions to our Partner REST API endpoint when an order is marked as paid.

== My eSIMs Page ==
- We’ve introduced the **My eSIMs page**, which provides an overview of eSIMs purchased by your customers if they are registered in your shop.
- Your users can now access their eSIMs, view installation instructions along with the QR code and all relevant details (including direct installation when accessing from iOS 17+), and monitor their eSIM data usage at any time.
- To implement the **My eSIMs page**, you need to **create a new page in WordPress and insert the provided shortcode [airalo_woocommerce_my_esim]**. The plugin takes care of the rest. Once you have saved and published your new page, you can add it to your menu to make it visible to your customers. This page can also be referenced in your WooCommerce email templates.

== 3rd party services ==
- Airalo PHP SDK: https://packagist.org/packages/airalo/sdk, https://github.com/Airalo/airalo-php-sdk; SDK built on top of Airalo's REST API
- Airalo Sandbox/Production REST API: sandbox.airalo.com and partner.airalo.com; used for QR code image dummy placeholders
link to our QR code service (My eSIM)
    - Privacy policy link: https://www.airalo.com/more-info/privacy-policy
    - Platform link: https://partners.airalo.com/
