<?php

/**
 * Plugin Name:       Advanced Campaign Monitor Integration
 * Plugin URI:        https://refact.co/
 * Description:       Advanced Campaign Monitor Integration is a powerful tool to synchronize data between Campaign Monitor and your WordPress site. Import all your content automatically and save time.
 * Version:           1.0.0
 * Author:            Refact
 * Author URI:        https://refact.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       advanced-campaign-monitor-integration
 * Domain Path:       /languages
 * Requires at least: 5.5.0
 * Requires PHP:      7.4+
 * Tested up to:      6.6.1
 * Stable tag:        1.0.0
 * Tags:              plugin, campaign monitor, newsletter, subscribe, form, widget, shortcode

 * Re/Campaign-Monitor Description
 * php version 5.6+
 *
 * @category Plugin
 * @package  ITCM_Campaign_Monitor
 * @author   Refact <dev@refact.co>
 * @license  GPL-2.0+ http://www.gnu.org/licenses/gpl-2.0.txt
 * @link     https://refact.co/
 **/

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

// Load Plugin File autoload.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('ITCM_CAMPAIGN_MONITOR_VERSION', '1.0.0');


/**
 * The plugin directory path
 */
define('ITCM_CAMPAIGN_MONITOR_PATH', plugin_dir_path(__FILE__));

/**
 * The plugin directory url
 */
define('ITCM_CAMPAIGN_MONITOR_URL', plugin_dir_url(__FILE__));

/**
 * The plugin __FILE__
 */
define('ITCM_CAMPAIGN_MONITOR_FILE', __FILE__);

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-itcm-campaign-monitor-core.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since  1.0.0
 * @return void
 */
function itcm_campaign_monitor_run()
{
	$plugin = new ITCM_Campaign_Monitor_Core();
	$plugin->run();
}

itcm_campaign_monitor_run();
