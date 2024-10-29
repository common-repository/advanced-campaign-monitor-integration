<?php
/**
 * The file contains the core plugin class.
 *
 * @package    ITCM_Campaign_Monitor
 * @since      1.0.0
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/includes
 * @author     Refact <info@refact.co>
 */
class ITCM_Campaign_Monitor_Core {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      ITCM_Campaign_Monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $itcm_campaign_monitor    The string used to uniquely identify this plugin.
	 */
	protected $itcm_campaign_monitor;

	/**
	 * The classes that are used to define the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $classes    The classes that are used to define the plugin.
	 */
	protected $classes;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ITCM_CAMPAIGN_MONITOR_CORE_VERSION' ) ) {
			$this->version = ITCM_CAMPAIGN_MONITOR_CORE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->itcm_campaign_monitor = 'itcm-campaign-monitor';

		$this->classes = apply_filters(
			'itcm_campaign_monitor_classes',
			array(
				ITCM_Campaign_Monitor\Init::class,
			)
		);

		$this->load_dependencies();
		$this->set_locale();
		$this->init_classes();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - ITCM_Campaign_Monitor_Loader. Orchestrates the hooks of the plugin.
	 * - ITCM_Campaign_Monitor_I18n. Defines internationalization functionality.
	 * - ITCM_Campaign_Monitor_Admin. Defines all hooks for the admin area.
	 * - ITCM_Campaign_Monitor_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-itcm-campaign-monitor-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-itcm-campaign-monitor-i18n.php';

		if ( ! class_exists( 'ActionScheduler' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		}

		$this->loader = new ITCM_Campaign_Monitor_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the ITCM_Campaign_Monitor_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new ITCM_Campaign_Monitor_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Init classes that need to be initialized
	 *
	 * @since 1.0.0
	 */
	public function init_classes() {
		foreach ( $this->classes as $class ) {
			$class_object = new $class();
			if ( method_exists( $class_object, 'init' ) ) {
				call_user_func( array( $class_object, 'init' ) );
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_itcm_campaign_monitor() {
		return $this->itcm_campaign_monitor;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    ITCM_Campaign_Monitor_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
