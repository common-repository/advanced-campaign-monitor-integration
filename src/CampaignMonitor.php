<?php
/**
 * This file contain the logic for the Campaign Monitor ESP
 * 
 * @package    ITCM_Campaign_Monitor
 * @since      1.0.0
 */

namespace ITCM_Campaign_Monitor;

use CS_REST_Clients;
use CS_REST_General;
use CS_REST_Lists;
use CS_REST_Subscribers;
use Refact\ESP_Core\Abstracts\Refact_ESP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CampaignMonitor
 * 
 * Handles the logic for the Campaign Monitor ESP
 */
class CampaignMonitor extends Refact_ESP {
	
	/**
	 * The Campaign Monitor client
	 * 
	 * @var CS_REST_Clients $campaign_monitor_client
	 */
	public CS_REST_Clients $campaign_monitor_client;

	/**
	 * The Campaign Monitor list client
	 * 
	 * @var CS_REST_Lists $campaign_monitor_lists
	 */
	public CS_REST_Lists $campaign_monitor_lists;

	/**
	 * The Campaign Monitor subscriber client
	 * 
	 * @var CS_REST_Subscribers $campaign_monitor_subscribers
	 */
	public CS_REST_Subscribers $campaign_monitor_subscribers;

	/**
	 * The API key for the Campaign Monitor client
	 * 
	 * @var string $api_key
	 */
	public $api_key;

	/**
	 * The client ID for the Campaign Monitor client
	 * 
	 * @var string $client_id
	 */
	public $client_id;

	/**
	 * The authentication array for the Campaign Monitor client
	 * 
	 * @var array $auth
	 */
	private $auth;

	/**
	 * CampaignMonitor constructor.
	 */
	public function __construct() {
		parent::__construct( 'campaign-monitor' );
	}

	/**
	 * Initialize the Campaign Monitor client.
	 */
	public function init(): void {
	}

	/**
	 * Get the lists from the Campaign Monitor client.
	 * 
	 * @return array|\WP_Error The lists or a WP_Error on failure.
	 */
	public function get_lists() {
		// Before get lists action.
		do_action( 'itcm_campaign_monitor_before_get_lists' );

		$this->get_credentials();

		// Get lists.
		$result = $this->campaign_monitor_client->get_lists();

		// Check result.
		if ( $result->was_successful() ) {
			$lists = $result->response;

			// After get lists action.
			do_action( 'itcm_campaign_monitor_after_get_lists', $lists );

			return $lists;
		} else {
			return new \WP_Error( 'campaign_monitor_error', $result->response->Message, array( 'code' => $result->http_status_code ) );
		}
	}

	/**
	 * Get API credentials.
	 * 
	 * @throws \InvalidArgumentException If the API key or Client ID are empty.
	 */
	public function get_credentials() {
		$campaign_monitor_settings = get_option( 'itcm_campaign_monitor_settings', array() );
		$this->api_key             = $campaign_monitor_settings['api_key'] ?? '';
		$this->client_id           = $campaign_monitor_settings['client_id'] ?? '';

		// Validate settings.
		if ( empty( $this->api_key ) || empty( $this->client_id ) ) {
			throw new \InvalidArgumentException( 'API key and client ID are required.' );
		}

		// Initialize auth.
		$this->auth = array( 'api_key' => $this->api_key );

		// Initialize client.
		$this->campaign_monitor_client = new CS_REST_Clients( $this->client_id, $this->auth );
	}

	/**
	 * Get a list from the Campaign Monitor client.
	 * 
	 * @param string $list_id The list ID.
	 * 
	 * @return array|\WP_Error The list or a WP_Error on failure.
	 */
	public function get_list( string $list_id ) {

		do_action( 'itcm_campaign_monitor_before_get_list', $list_id );

		$this->get_credentials();

		$this->campaign_monitor_lists = new CS_REST_Lists( $list_id, $this->auth );
		$result                       = $this->campaign_monitor_lists->get();

		if ( $result->was_successful() ) {
			$list = $result->response;
			do_action( 'itcm_campaign_monitor_after_get_list', $list, $list_id );
			return $list;
		} else {
			return new \WP_Error( 'campaign_monitor_error', $result->response->Message, array( 'code' => $result->http_status_code ) );
		}
	}

	/**
	 * Get a subscriber from the Campaign Monitor client.
	 * 
	 * @param string $email The subscriber email.
	 * @param string $list_id The list ID.
	 * 
	 * @return array|\WP_Error The subscriber or a WP_Error on failure.
	 */
	public function get_subscriber( string $email, string $list_id = '' ) {

		$this->get_credentials();

		do_action( 'itcm_campaign_monitor_before_get_subscriber', $email, $list_id );
		$result = array();
		$email  = $this->is_email( $email );
		if ( ! $email ) {
			$result = new \WP_Error( '400', __( 'Invalid email address', 'advanced-campaign-monitor-integration' ) );
		} else {
			$this->$campaign_monitor_subscribers = new CS_REST_Subscribers( $list_id, $this->auth );
			$result                              = $this->$campaign_monitor_subscribers->get( $email, true );
		}

		return apply_filters( 'itcm_campaign_monitor_get_subscriber', $result, $email, $list_id );
	}

	/**
	 * Update a subscriber in the Campaign Monitor client.
	 * 
	 * @param string $email The subscriber email.
	 * @param array  $data The subscriber data.
	 * @param string $list_id The list ID.
	 */
	public function update_subscriber( string $email, array $data, string $list_id = '' ) {
		$this->add_subscriber( $email, $data, $list_id );
	}

	/**
	 * Add a subscriber to the Campaign Monitor client.
	 * 
	 * @param string $email The subscriber email.
	 * @param array  $data The subscriber data.
	 * @param string $list_id The list ID.
	 * 
	 * @return array|\WP_Error The result of the add subscriber request or a WP_Error on failure.
	 */
	public function add_subscriber( string $email, array $data, string $list_id = '' ) {
		do_action( 'itcm_campaign_monitor_before_add_subscriber', $email, $data, $list_id );

		$this->get_credentials();


		// Change data to match the Campaign Monitor API.
		$custom_fields = array();
		foreach ( $data['vars'] as $key => $value ) {
			if ( strtolower( $key ) == 'name' ) {
				$data['name'] = $value;
				continue;
			}

			$custom_fields[] = array(
				'Key'   => $key,
				'Value' => $value,
			);
		}

		$modified_data = array(
			'EmailAddress'   => $email,
			'Name'           => $data['name'],
			'MobileNumber'   => $data['MobileNumber'],
			'CustomFields'   => $custom_fields,
			'ConsentToTrack' => 'yes',
			'Resubscribe'    => true,
		);
		
		$email = $this->is_email( $email );
		if ( ! $email ) {
			$result = new \WP_Error( 'invalid_email', __( 'Invalid email address', 'advanced-campaign-monitor-integration' ) );
		} else {
			$this->$campaign_monitor_subscribers = new CS_REST_Subscribers( $list_id, $this->auth );
			$result                              = $this->$campaign_monitor_subscribers->add( $modified_data );
		}

		do_action( 'itcm_campaign_monitor_after_add_subscriber', $result, $email, $data, $list_id );

		return apply_filters( 'itcm_campaign_monitor_add_subscriber', $result, $email, $data, $list_id );
	}

	/**
	 * Map the data from the WordPress user to the Campaign Monitor subscriber.
	 * 
	 * @param \WP_User $user The WordPress user.
	 * @param array    $tasks The tasks to map.
	 * @param array    $args The arguments.
	 * 
	 * @return array The mapped data.
	 */
	public function map_data( $user, array $tasks, $args = array() ): array {
		$map = array( 'vars' => array() );

		foreach ( $tasks as $task ) {
			$wp_field_name  = $task['wp'];
			$esp_field_name = $task['esp'];

			$wp_field_value = $user->$wp_field_name;

			$map['vars'][ $esp_field_name ] = $wp_field_value;
		}

		return $map;
	}
}
