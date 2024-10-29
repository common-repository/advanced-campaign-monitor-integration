<?php
/**
 * The admin menu class
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/src 
 */

namespace ITCM_Campaign_Monitor;

/**
 * The admin menu class
 * 
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/src
 */
class AdminMenu {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {

		$page_hook_suffix = add_options_page(
			__( 'Advanced Campaign Monitor Integration', 'advanced-campaign-monitor-integration' ),
			__( 'Advanced Campaign Monitor Integration', 'advanced-campaign-monitor-integration' ),
			'manage_options',
			'itcm-campaign-monitor',
			array( $this, 'admin_page_callback' )
		);

		add_action( "admin_print_scripts-{$page_hook_suffix}", array( $this, 'add_admin_assets' ) );
	}

	/**
	 * Admin menu page callback.
	 *
	 * @since    1.0.0
	 */
	public function admin_page_callback() {
		?>
		<div id="itcm-campaign-monitor">            
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since    1.0.0
	 */
	public function add_admin_assets() {
		wp_enqueue_script(
			're-esp-campaign-monitor-admin',
			ITCM_CAMPAIGN_MONITOR_URL . 'build/index.js',
			array( 'wp-api', 'wp-i18n', 'wp-components', 'wp-element', 'wp-api-fetch' ),
			ITCM_CAMPAIGN_MONITOR_VERSION,
			true 
		);

		// Enqueue the React Toastify CSS.
		wp_enqueue_style(
			're-esp-campaign-monitor-toastify',
			ITCM_CAMPAIGN_MONITOR_URL . 'lib/assets/css/reactToastify.css',
			array(),
			'8.0.3'
		);

		wp_enqueue_style(
			're-esp-campaign-monitor-tooltip',
			ITCM_CAMPAIGN_MONITOR_URL . 'lib/assets/css/react-tooltip.css',
			array(),
			'8.0.3'
		);
	}
}