<?php
/**
 * This File Contains the Init Class of the Plugin.
 * 
 * @package Re-Campaign-Monitor
 * @since 1.0.0
 */

namespace ITCM_Campaign_Monitor;

use ITCM_Campaign_Monitor\AdminMenu;
use ITCM_Campaign_Monitor\APICredentials;
use ITCM_Campaign_Monitor\CampaignMonitor;
use ITCM_Campaign_Monitor\ImportCampaigns\ImportCampaigns;
use Refact\ESP_Core\API\RegisterEndpoints;
use Refact\ESP_Core\Handlers\SyncHandler;
use Refact\ESP_Core\RegisterActivation\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The init class.
 *
 * Handles the settings front-end and back-end functionality of the plugin.
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/includes
 */
class Init {

	/**
	 * The Campaign Monitor instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      CampaignMonitor    $campaign_monitor    The Campaign Monitor instance.
	 */
	protected $campaign_monitor;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      ITCM_Campaign_Monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	public function __construct() {
		
		new AdminMenu();
		new APICredentials();
		new ImportCampaigns();

		$this->campaign_monitor = new CampaignMonitor();
		

		new Base( ITCM_CAMPAIGN_MONITOR_FILE );
		new RegisterEndpoints( $this->campaign_monitor );
		new SyncHandler( $this->campaign_monitor );

		add_filter( 're_esp_campaign-monitor_get_lists_endpoint', array( $this, 'handle_get_lists_endpoint' ), 10, 2 );
		add_filter( 're_esp_campaign-monitor_get_rules_endpoint', array( $this, 'handle_get_rules_endpoint' ), 10, 2 );
	}

	/**
	 * Handles the get lists endpoint
	 * 
	 * @param mixed  $response The response from the endpoint.
	 * @param object $esp The Campaign Monitor instance.
	 * 
	 * @return mixed
	 */
	public function handle_get_lists_endpoint( $response, $esp ) {
		$rest_response = new \WP_REST_Response();
		$lists         = $this->campaign_monitor->get_lists();

		if ( is_wp_error( $lists ) ) {
			$rest_response->set_data( $lists );
			$rest_response->set_status( \WP_Http::BAD_REQUEST );
			return $rest_response;
		}

		$lists = array_map(
			function ( $ls ) {
				return array(
					'value' => $ls->ListID,
					'label' => $ls->Name,
				);
			},
			$lists
		);

		$rest_response->set_data( $lists );
		$rest_response->set_status( \WP_Http::OK );

		return $rest_response;
	}

	/**
	 * Handles the get rules endpoint
	 * 
	 * @param mixed  $response The response from the endpoint.
	 * @param object $request The request object.
	 * 
	 * @return mixed
	 */
	public function handle_get_rules_endpoint( $response, $request ) {
		foreach ( $response as  $rule ) {
			$rule->tasks = json_decode( $rule->tasks );
		}
		return $response;
	}
}
