<?php
/**
 * The file contains the API Credentials settings init class.
 * 
 * @package    ITCM_Campaign_Monitor
 * @since      1.0.0
 */

namespace ITCM_Campaign_Monitor;

/**
 * The settings init class.
 *
 * Handles the Api Credentials settings back-end functionality 
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/includes
 */
class APICredentials {
	/**
	 * The loader that's responsible for maintaining and registering Api Credentials Settings
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      ITCM_Campaign_Monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_api_credential_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_api_credential_rest_route' ), 10, 0 );
	}


	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function register_api_credential_settings() {

		register_setting(
			'itcm_campaign_monitor_settings',
			'api_key',
			array(
				'type'         => 'string',
				'show_in_rest' => true,
			)
		);
	
		register_setting(
			'itcm_campaign_monitor_settings',
			'client_id',
			array(
				'type'         => 'string',
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Register rest route
	 *
	 * @since    1.0.0
	 */
	public function register_api_credential_rest_route() {
		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/get-settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_setting' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) 
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/save-settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_setting' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) 
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/delete-settings',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_setting' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) 
		);
	}

	/**
	 * Check api availability
	 *
	 * @param    \WP_REST_Request $request   The request object.
	 * @since    1.0.0
	 */
	public function get_setting( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'rest_forbidden', 'Sorry, you are not allowed to get settings.', array( 'status' => 403 ) );
		}

		$campaign_monitor_settings = get_option( 'itcm_campaign_monitor_settings', array() );

		return rest_ensure_response( $campaign_monitor_settings );
	}

	/**
	 * Save settings
	 *
	 * @param    \WP_REST_Request $request   The request object.
	 * @since    1.0.0
	 */
	public function save_setting( \WP_REST_Request $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to save settings.', 'advanced-campaign-monitor-integration' ), array( 'status' => 403 ) );
		}

		$campaign_monitor_settings = get_option( 'itcm_campaign_monitor_settings', array() );

		$api_key   = sanitize_text_field( $request->get_param( 'api_key' ) );
		$client_id = sanitize_text_field( $request->get_param( 'client_id' ) );

		if ( empty( $api_key ) || empty( $client_id ) ) {
			return new \WP_Error( 'required_fields', __( 'API key and client ID are required.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}


		if ( ! self::is_credentials_valid( $api_key, $client_id ) ) {
			return new \WP_Error( 'invalid_credentials', __( 'Invalid API key or client ID.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		$campaign_monitor_settings['api_key']   = $api_key;
		$campaign_monitor_settings['client_id'] = $client_id;
		update_option( 'itcm_campaign_monitor_settings', $campaign_monitor_settings );

		return rest_ensure_response( $campaign_monitor_settings );
	}

	/**
	 * Delete settings
	 *
	 * @param    \WP_REST_Request $request   The request object.
	 * @since    1.0.0
	 */
	public function delete_setting( \WP_REST_Request $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to delete settings.', 'advanced-campaign-monitor-integration' ), array( 'status' => 403 ) );
		}

		$campaign_monitor_settings = get_option( 'itcm_campaign_monitor_settings', array() );

		$campaign_monitor_settings['api_key']   = '';
		$campaign_monitor_settings['client_id'] = '';

		update_option( 'itcm_campaign_monitor_settings', $campaign_monitor_settings );

		return rest_ensure_response( $campaign_monitor_settings );
	}

	/**
	 * Check if the credentials are valid
	 *
	 * @param    string $api_key   The API key.
	 * @param    string $client_id The client ID.
	 * @since    1.0.0
	 */
	public static function is_credentials_valid( $api_key, $client_id ) {
		$campaign_monitor_settings = get_option( 'itcm_campaign_monitor_settings', array() );
		// Check if the API key and client ID are set.
		if ( ! isset( $campaign_monitor_settings['api_key'] ) || ! isset( $campaign_monitor_settings['client_id'] ) ) {
			return array(
				'error'   => 'missing_credentials',
				'message' => __( 'API key and client ID are required.', 'advanced-campaign-monitor-integration' ),
			);
		}

	
		$url     = "https://api.createsend.com/api/v3.2/clients/$client_id/lists.json";
		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ),
			'Content-Type'  => 'application/json',
		);
		//phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response = wp_remote_get( $url, array( 'headers' => $headers ) );
		if ( is_wp_error( $response ) ) {
			return array(
				'error'   => 'request_failed',
				'message' => $response->get_error_message(),
			);
		}
	
		$status_code = wp_remote_retrieve_response_code( $response );
		
		return ( 200 == $status_code ) ? true : false;
	}
}
