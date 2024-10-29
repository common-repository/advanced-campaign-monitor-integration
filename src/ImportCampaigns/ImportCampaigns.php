<?php
/**
 * The file contains the Import Campaigns Class.
 * 
 * @package Re-Campaign-Monitor
 * @since 1.0.0
 */

namespace ITCM_Campaign_Monitor\ImportCampaigns;

use CS_REST_Clients;
use ITCM_Campaign_Monitor\ImportCampaigns\BackgroundProcessing\ImportCampaignsProcess;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Import Campaigns Class.
 *
 * Handles the import campaigns functionality of the plugin.
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/includes
 */
class ImportCampaigns {
	/**
	 * The Campaign Monitor client
	 * 
	 * @var CS_REST_Clients $campaign_monitor_client
	 */
	public CS_REST_Clients $campaign_monitor_client;

	/**
	 * The validation handler.
	 * 
	 * @var ImportCampaignsValidator $validator
	 */
	protected $validator;


	/**
	 * The campaigns to import.
	 * 
	 * @var array $campaigns
	 */
	protected $campaigns;

	/**
	 * The import campaigns process.
	 * 
	 * @var ImportCampaignsProcess $import_campaigns_process
	 */
	protected $import_campaigns_process;


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * 
	 * @since    1.0.0
	 * @access   protected
	 * @var      ITCM_Campaign_Monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'handle_background_processes' ) );
		add_action( 'rest_api_init', array( $this, 'register_import_campaigns_endpoint' ) );
		add_action( 'plugins_loaded', ImportCampaignsHelper::class . '::include_action_scheduler' );
		add_action( 'itcm_import_campaigns', array( $this, 'handle_scheduled_import' ) );
		$this->validator = new ImportCampaignsValidator();
	}

	/**
	 * Register the import campaigns endpoint.
	 *
	 * @since    1.0.0
	 */
	public function register_import_campaigns_endpoint() {
		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/import-defaults-options',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'import_defaults_options' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) 
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/import-campaigns',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_campaigns' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) 
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/import-status',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) 
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/manage-import-job',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'manage_import_job' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/get-scheduled-imports',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_scheduled_imports' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			're-esp-campaign-monitor/v1',
			'/delete-scheduled-import/',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_scheduled_import' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get import defaults options
	 *
	 * @param    \WP_REST_Request $request   The request object.
	 * @since    1.0.0
	 */
	public function import_defaults_options( \WP_REST_Request $request ) {
		$data = array();

		// All post types and taxonomies and terms.
		$data = array_merge( $data, ImportCampaignsHelper::get_all_post_types_tax_term() );
		
		// Current server time.
		$data['current_server_time'] = gmdate( '(D) H:i' );

		// All post statuses.
		$data = array_merge( $data, ImportCampaignsHelper::get_all_post_statuses() );

		// All authors users.
		$data = array_merge( $data, ImportCampaignsHelper::get_all_authors() );

		
		return rest_ensure_response( $data );
	}

	/**
	 * Import campaigns.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import_campaigns( $request ) {
		// Get all parameters.
		$params = array(
			'credentials'       => json_decode( sanitize_text_field( $request->get_param( 'credentials' ) ), true ),
			'post_status'       => json_decode( sanitize_text_field( $request->get_param( 'post_status' ) ), true ),
			'schedule_settings' => json_decode( sanitize_text_field( $request->get_param( 'schedule_settings' ) ), true ),
			'post_type'         => sanitize_text_field( $request->get_param( 'post_type' ) ),
			'taxonomy'          => sanitize_text_field( $request->get_param( 'taxonomy' ) ),
			'taxonomy_term'     => sanitize_text_field( $request->get_param( 'taxonomy_term' ) ),
			'author'            => sanitize_text_field( $request->get_param( 'author' ) ),
			'import_cm_tags_as' => sanitize_text_field( $request->get_param( 'import_cm_tags_as' ) ),
			'import_option'     => sanitize_text_field( $request->get_param( 'import_option' ) ),
		);
		
		// Validate all parameters.

		$validation = $this->validator->validate_all_parameters( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$this->campaigns = ImportCampaignsHelper::fetch_all_campaigns( $params['credentials'], $params['post_status'] );
		if ( is_wp_error( $this->campaigns ) ) {
			return $this->campaigns;
		}
		
		// Start importing campaigns.
		$this->start_importing_campaigns( $this->campaigns, $params );

		$output = array(
			'message' => 'Campaigns fetched successfully. Importing campaigns...',
		);

		if ( 'on' === $params['schedule_settings']['enabled'] ) {
			$schedule_import_result = ImportCampaignsHelper::schedule_import_campaigns( $params );
			if ( is_wp_error( $schedule_import_result ) ) {
				$output['schedule_id'] = $schedule_import_result->get_error_message();
			} else {
				$output['schedule_id'] = $schedule_import_result;
			}
		}

		return rest_ensure_response( $output );
	}

	/**
	 * Start fetching campaigns.
	 *
	 * @param array $campaigns The campaigns array.
	 * @param array $params The parameters array.
	 */
	private function start_importing_campaigns( $campaigns, $params ) {

		// Get all past imported campaigns.
		$past_imported_campaigns = ImportCampaignsHelper::get_past_imported_campaigns( $params );
		
		// Filter campaigns based on import option.
		$campaigns = ImportCampaignsHelper::filter_campaigns( $campaigns, $past_imported_campaigns, $params['import_option'] );
	
		$campaign_count = 0;
		foreach ( $campaigns as $status => $campaigns ) {
			foreach ( $campaigns as $campaign ) {
				$this->import_campaigns_process->push_to_queue(
					array(
						'campaign'        => $campaign,
						'campaign_status' => $status,
						'params'          => $params,
					)
				);
				++$campaign_count;
			}
		}

		set_transient( 'itcm_total_campaigns', $campaign_count, 0 );

		$this->import_campaigns_process->save();
		$this->import_campaigns_process->dispatch();
	}

	/**
	 * Get import status
	 *
	 * @param    \WP_REST_Request $request   The request object.
	 * @since    1.0.0
	 */
	public function import_status( $request ) {

		if ( $this->import_campaigns_process->is_active() ) {

			if ( $this->import_campaigns_process->is_paused() ) {
				$output['status'] = 'paused';
			} else {
				$output['status'] = 'active';
			}
			
			$batches             = $this->import_campaigns_process->get_batches();
			$remaining_campaigns = 0;
			foreach ( $batches as $batch ) {
				$remaining_campaigns += count( $batch->data );
			}
			$output['remaining_campaigns'] = $remaining_campaigns;
			$output['total_campaigns']     = get_transient( 'itcm_total_campaigns' );
	
		} else {
			$output['status'] = 'not_active';
		}

		return rest_ensure_response( $output );
	}

	/**
	 * Handle the background processes.
	 */
	public function handle_background_processes() {
			$this->import_campaigns_process = new ImportCampaignsProcess();
	}

	/**
	 * Manage the import job (cancel, pause, resume).
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function manage_import_job( \WP_REST_Request $request ) {
		$job_action = sanitize_text_field( $request->get_param( 'job_action' ) );

		if ( ! $this->import_campaigns_process->is_active() ) {
			return new \WP_Error( 'no_active_process', 'There is no active import process to manage.', array( 'status' => 400 ) );
		}

		switch ( $job_action ) {
			case 'cancel':
				$this->import_campaigns_process->cancel();
				delete_transient( 'itcm_total_campaigns' );
				$response = array(
					'status'  => 'canceled',
					'message' => 'Import process has been canceled.',
				);
				break;

			case 'pause':
				$this->import_campaigns_process->pause();
				$response = array(
					'status'  => 'paused',
					'message' => 'Import process has been paused.',
				);
				break;

			case 'resume':
				$this->import_campaigns_process->resume();
				$response = array(
					'status'  => 'resumed',
					'message' => 'Import process has been resumed.',
				);
				break;

			default:
				return new \WP_Error( 'invalid_job_action', "Invalid job action provided: {$job_action}", array( 'status' => 400 ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle scheduled import.
	 * 
	 * @param array $params The parameters array.
	 * @return \WP_Error
	 */
	public function handle_scheduled_import( $params ) {
		$campaigns = ImportCampaignsHelper::fetch_all_campaigns( $params['credentials'], $params['post_status'] );
		if ( is_wp_error( $campaigns ) ) {
			return $campaigns;
		}
		$this->start_importing_campaigns( $campaigns, $params );
	}

	/**
	 * Get all scheduled imports.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_scheduled_imports( \WP_REST_Request $request ) {
		$group_name = 'itcm_import_campaigns_group';
		
		// Fetch scheduled actions with the specified group name and status.
		$actions = as_get_scheduled_actions(
			array(
				'group'  => $group_name,
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);
	
		// Initialize an array to store formatted actions.
		$formatted_actions = array();
	
		// Iterate over each action ID to fetch and format the action details.
		foreach ( $actions as $action_id ) {
			$action = \ActionScheduler::store()->fetch_action( $action_id );
			if ( $action ) {
				$formatted_actions[] = array(
					'id'     => $action_id,
					'params' => $action->get_args(),
				);
			}
		}
	
		// Ensure the response is properly formatted as a REST response.
		return rest_ensure_response( $formatted_actions );
	}

	/**
	 * Delete a scheduled import.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function delete_scheduled_import( \WP_REST_Request $request ) {
		// Retrieve the schedule ID from the request parameters.
		$schedule_id = $request->get_param( 'id' );
		
		// Check if the schedule ID is valid.
		if ( ! $schedule_id ) {
			return new \WP_Error(
				'invalid_schedule_id',
				'Schedule ID is required.',
				array( 'status' => 400 )
			);
		}
	
		// Fetch the action with the specified ID.
		$action = \ActionScheduler::store()->fetch_action( intval( $schedule_id ) );
		if ( is_null( $action ) || $action instanceof \ActionScheduler_NullAction ) {
			return new \WP_Error(
				'invalid_schedule_id',
				'Schedule ID does not exist.',
				array( 'status' => 400 )
			);
		}
	
		try {
			// Attempt to delete the action.
			\ActionScheduler::store()->delete_action( intval( $schedule_id ) );
		} catch ( Exception $e ) {
			return new \WP_Error(
				'failed_delete',
				'Failed to delete the scheduled import.',
				array( 'status' => 500 )
			);
		}
	
		// Return a success response.
		return rest_ensure_response(
			array(
				'message' => 'Scheduled import has been deleted.',
				'id'      => $schedule_id,
			)
		);
	}
}
