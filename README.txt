=== Advanced Campaign Monitor Integration - Sync WP users and newsletter subscribers data, import campaigns ===

Contributors: refact, saeedja, masoudin
Requires at least: 5.5.0
Tested up to:      6.6.1
Requires PHP:      7.4
Stable tag:        1.0.0
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: newsletter, Campaign Monitor, import, email, importer

Advanced Campaign Monitor Integration syncs user data with Campaign Monitor and imports campaigns to WordPress.

== Description ==

Advanced Campaign Monitor Integration provides you with the necessary integration to import content automatically between Campaign Monitor and your WordPress site.

This plugin is designed to be simple and ensures your WordPress site is always updated with the latest content. "Advanced Campaign Monitor Integration" allows you to:

* Sync WP users and newsletter subscribers data
  * Set up sync rules to automatically sync user data to Campaign Monitor
  * Choose the trigger type, object, condition, and list for the sync rule
* Import campaigns from Campaign Monitor
  * Set API key and Client ID to connect your Campaign Monitor account
  * Choose which campaigns to import from Campaign Monitor (published, draft, scheduled, etc.)
  * Determine the post status for the imported content (published, draft, pending, etc.)
  * Choose the post type, taxonomy, and term for the imported campaigns in WordPress
  * Select the WordPress user to assign the imported campaigns to
  * Decide whether to import Campaign Monitor campaign tags as WordPress post tags or categories
  * Choose to import only new campaigns, update existing campaigns, or both
  * Configure the import schedule frequency for importing campaigns from Campaign Monitor (Hourly, Daily, Weekly, etc.)

In addition to its importing capabilities, this version of the "Advanced Campaign Monitor Integration" integrates perfectly with Yoast SEO plugin, allowing you to add canonical tags to all your imported content. This integration helps in improving your SEO ranking by preventing duplicate content issues and directing search engines to the original content.

While “Advanced Campaign Monitor Integration” is not officially developed or endorsed by Campaign Monitor, it adheres to all best practices and protocols, ensuring a secure and effective synchronization between your WordPress site and Campaign Monitor.

== Third-Party Service Information ==

This plugin uses the Campaign Monitor API, a third-party service, to facilitate the import of campaign data and the synchronization of user data between your WordPress site and Campaign Monitor. The specific external service endpoints used by this plugin are:

Campaign Lists: Data regarding the lists associated with a Campaign Monitor client account is fetched using the API endpoint https://api.createsend.com/api/v3.2/clients/{client_id}/lists.json.
Campaign Import: Campaign content and associated metadata are imported from Campaign Monitor using its API. Data such as campaign content, author information, tags, and other related metadata are retrieved and transferred to your WordPress site.
Circumstances under which data is sent:
Manual or Automatic Campaign Import: Data is transmitted when campaigns are manually imported or automatically updated from Campaign Monitor to WordPress.
User Data Synchronization: When synchronizing user data between WordPress and Campaign Monitor, user details like email addresses, names, and custom fields might be transmitted to Campaign Monitor.
Data Sent to Campaign Monitor:
User Data: When syncing users, the plugin sends user details such as email addresses, names, and any other user data fields specified in the synchronization settings.
Campaign Selection Data: When importing campaigns, information about which campaigns to import, the selected post status, post type, taxonomy, and other options are sent to Campaign Monitor's API to fetch the correct data.
Links to Service Terms and Policies:
Campaign Monitor API Documentation: Campaign Monitor API Homepage
Terms of Use: Campaign Monitor Terms of Use
Privacy Policy: Campaign Monitor Privacy Policy
Please ensure that you review Campaign Monitor’s privacy policy and terms of service to understand how they handle your data and to ensure that the usage of this plugin is compliant with your local regulations.


== Third-Party Packages ==

**Composer Packages**

* refactco/re-esp-core: Provides core functionalities for the plugin.
* campaignmonitor/createsend-php: PHP wrapper for the Campaign Monitor API.
* deliciousbrains/wp-background-processing: Library for background processing tasks in WordPress.
* woocommerce/action-scheduler: A robust scheduler for managing scheduled tasks in WordPress.

**NPM Packages**

* @wordpress/scripts: Collection of scripts for WordPress plugin development.
* @refactco/ui-kit: Custom UI kit for building the plugin interface.
* @wordpress/api-fetch: A package for making API requests in WordPress.
* @wordpress/icons: WordPress icons library.
* react-router-dom: Library for routing in React applications.
* react-toastify: Library for displaying toast notifications in React.
* styled-components: Library for writing CSS in JavaScript.

== Legal and Security ==

The secure handling of your data is paramount. We use industry-standard security measures during data transmission to Campaign Monitor. By using this plugin, you acknowledge and consent to the transfer of data to Campaign Monitor as described in the Third-Party Service Information section. It is your responsibility to ensure that the use of Campaign Monitor’s API complies with all relevant legal requirements applicable to your geographical location.

While “Advanced Campaign Monitor Integration” is not officially developed or endorsed by Campaign Monitor, it adheres to all best practices and protocols, ensuring a secure and effective synchronization between your WordPress site and Campaign Monitor.

== We want your input ==

If you have any suggestions for improvements, feature updates, etc., or would like to simply give us feedback, then we want to hear it. Please email your comments to [dev@refact.co](mailto:dev@refact.co).

== Frequently Asked Questions ==

= What is "Advanced Campaign Monitor Integration"? =

"Advanced Campaign Monitor Integration" is a WordPress plugin designed to seamlessly integrate and synchronize your Campaign Monitor data with your WordPress website. It provides a user-friendly interface for manual or auto-import of your Campaign Monitor content directly into your WordPress.

= How do I connect my Campaign Monitor account to the plugin? =

Once you’ve installed and activated the "Advanced Campaign Monitor Integration" plugin, navigate to the plugin’s settings in your WordPress dashboard. Here, you’ll be prompted to input your Campaign Monitor API key and Client ID to establish the connection.

= Can I choose which campaigns to import from Campaign Monitor? =

Yes, the plugin allows you to select the campaigns you wish to import from Campaign Monitor. You can choose to import published, draft, or scheduled campaigns based on your requirements.

= How frequently can I set the auto-import feature? =

You can configure the import schedule frequency for importing campaigns from Campaign Monitor. The plugin offers a range of options, including Hourly, Daily, Weekly, etc., to suit your needs.

= Will my Campaign Monitor tags be imported as well? =

Absolutely! The plugin ensures that your Campaign Monitor tags are imported, helping maintain the organization and structure of your content on your WordPress site.

= Is there a way to select a specific author for the imported content? =

Yes, when setting up your import preferences, you have the option to assign the imported campaigns to a specific user or author in your WordPress setup.

= I’m facing an issue with the plugin. How can I get support? =

We’re here to help! Please contact us through the WordPress support forum or email us at [dev@refact.co](mailto:dev@refact.co) with your query. We'll respond to you as soon as possible.

= Is my Campaign Monitor data secure during the import process? =

The "Advanced Campaign Monitor Integration" plugin establishes a secure connection using your Campaign Monitor API key to ensure a safe and trusted data transfer process.

= Can I disconnect my Campaign Monitor account from the plugin? =

Yes, at any time you can navigate to the plugin’s settings in your dashboard and click the ‘Disconnect’ button to unlink your Campaign Monitor account.

== Installation ==

= Installation from within WordPress =

1. Visit Plugins > Add New.
2. Search for "Advanced Campaign Monitor Integration - Sync WP users and newsletter subscribers data, import campaigns" in the search bar.
3. Install and activate the 'Advanced Campaign Monitor Integration' plugin.
4. After activation, go to the Settings section in your WordPress dashboard, and then navigate to the Advanced Campaign Monitor Integration section.
5. Follow the on-screen instructions to set up content imports and synchronization.

= Manual installation =

1. Download the plugin zip file.
2. Upload it to the `/wp-content/plugins/` directory in your website.
3. Visit the Plugins in your WordPress Dashboard.
4. Locate 'Advanced Campaign Monitor Integration' and activate it.
5. After activation, go to the Settings section in your WordPress dashboard, and then navigate to the Advanced Campaign Monitor Integration section.
6. Follow the on-screen instructions to set up content imports and synchronization.

== Screenshots ==

1. The Settings page: Configure your Campaign Monitor API settings.
2. Sync Rules: Manage and add new sync rules to define how data user data should be synchronized between Campaign Monitor and WordPress.
3. Add New Rule: Add new sync rules by specifying the trigger type, object, condition, list, and status.
4. Edit Rule: Edit existing sync rules to update synchronization preferences.
5. Import Campaign Data - Step 1: Choose the data to import from Campaign Monitor.
6. Import Campaign Data - Step 2: Configure how the imported data should be structured in WordPress.
7. Set the import schedule frequency for importing campaigns from Campaign Monitor.

== Upgrade Notice ==

= v1.0.0 =
New: Initial release

== Changelog ==

= v1.0.0 =
New: Initial release
