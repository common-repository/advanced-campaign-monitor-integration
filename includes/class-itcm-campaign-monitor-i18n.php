<?php
/**
 * The file contains the plugin internationalization class.
 *
 * @package    ITCM_Campaign_Monitor
 * @since      1.0.0
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @category   Plugin
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/includes
 * @author     Refact <info@refact.co>
 * @license    GPL-2.0+ http://www.gnu.org/licenses/gpl-2.0.txt
 * @link       https://refact.co/
 * @since      1.0.0
 */
class ITCM_Campaign_Monitor_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'itcm-campaign-monitor',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
