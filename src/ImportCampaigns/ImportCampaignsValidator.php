<?php
/** 
 * This File Contains the Campaign Import Validator Class.
 * 
 * @package Re-Campaign-Monitor
 * @subpackage Importcampaigns
 * @since 1.0.0
 */

namespace ITCM_Campaign_Monitor\ImportCampaigns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Campaign Import Validator class.
 *
 * Handles the validation of the campaign data before importing.
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/includes
 */
class ImportCampaignsValidator {

	/**
	 * Validate all parameters.
	 *
	 * @param array $params Parameters to validate.
	 * @return mixed True if valid, otherwise WP_Error.
	 */
	public function validate_all_parameters( $params ) {

		// Validate credentials.
		$valid_credentials = $this->validate_credentials( $params['credentials'] );
		if ( is_wp_error( $valid_credentials ) ) {
			return $valid_credentials;
		}

		// Validate basic parameters.
		$valid_params = $this->validate_parameters( $params );
		if ( is_wp_error( $valid_params ) ) {
			return $valid_params;
		}

		// Validate post status.
		$valid_post_status = $this->validate_post_status( $params['post_status'] );
		if ( is_wp_error( $valid_post_status ) ) {
			return $valid_post_status;
		}

		// Validate post target.
		$valid_post_target = $this->validate_post_target( $params['post_type'], $params['taxonomy'], intval( $params['taxonomy_term'] ) );
		if ( is_wp_error( $valid_post_target ) ) {
			return $valid_post_target;
		}

		// Validate author.
		$author = get_user_by( 'ID', $params['author'] );
		if ( ! $author ) {
			return new \WP_Error( 'invalid_author', __( 'Invalid author.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		// Validate import option.
		if ( ! in_array( $params['import_option'], array( 'new', 'update', 'both' ) ) ) {
			return new \WP_Error( 'invalid_import_option', __( 'Invalid import option.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		// Validate import_cm_tags_as option.
		if ( ! in_array( $params['import_cm_tags_as'], array( 'category', 'post_tag' ) ) ) {
			return new \WP_Error( 'invalid_import_cm_tags_as', __( 'Invalid import_cm_tags_as option.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		// Validate schedule settings.
		$valid_schedule_settings = $this->validate_schedule_settings( $params['schedule_settings'] );
		if ( is_wp_error( $valid_schedule_settings ) ) {
			return $valid_schedule_settings;
		}
	
		return true;
	}

	/**
	 * Validate API credentials.
	 *
	 * @param array $credentials The credentials array.
	 * @return mixed True if valid, otherwise WP_Error.
	 */
	public function validate_credentials( $credentials ) {
		$api_key   = trim( $credentials['api_key'] );
		$client_id = trim( $credentials['client_id'] );
		if ( 152 !== strlen( $api_key ) ) {
			return new \WP_Error( 'invalid_api_key', __( 'Invalid API key format', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		if ( 32 !== strlen( trim( $client_id ) ) ) {
			return new \WP_Error( 'invalid_client_id', __( 'Invalid Client ID format', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
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

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( 200 !== $response_code ) {
			return new \WP_Error( 'invalid_credentials', __( 'API key or Client ID is not correct', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Validate required parameters.
	 *
	 * @param array $params Parameters to validate.
	 * @return mixed True if valid, otherwise WP_Error.
	 */
	public function validate_parameters( $params ) {
		foreach ( $params as $key => $param ) {
			if ( empty( $param ) ) {
				return new \WP_Error( 'missing_parameters', __( 'Missing required parameters.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
			}
		}
		return true;
	}

	/**
	 * Validate post status.
	 *
	 * @param array $post_status The post status array.
	 * @return mixed True if valid, otherwise WP_Error.
	 */
	public function validate_post_status( $post_status ) {
		$post_status_keys = array_keys( $post_status );
		foreach ( $post_status_keys as $status ) {
			if ( ! in_array( $status, array( 'scheduled', 'draft', 'published' ) ) ) {
				return new \WP_Error( 'invalid_post_status', __( 'Invalid post status.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
			}
		}

		$wp_post_statuses_keys = array_keys( ImportCampaignsHelper::get_all_post_statuses()['post_statuses'] );

		foreach ( $post_status as $status => $value ) {
			if ( ! in_array( $value, $wp_post_statuses_keys ) ) {
				// translators: %s: WordPress status value.
				return new \WP_Error( 'invalid_post_status_value', sprintf( __( 'Invalid WordPress status value [%s]', 'advanced-campaign-monitor-integration' ), $value ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * Validates if the specified post type, taxonomy, and term are valid.
	 *
	 * @param string $post_type The post type.
	 * @param string $taxonomy The taxonomy slug.
	 * @param int    $taxonomy_term The taxonomy term ID.
	 * @return bool|WP_Error Returns true if valid, otherwise WP_Error.
	 */
	public function validate_post_target( $post_type, $taxonomy, $taxonomy_term ) {
		$all_post_type_tax_term = ImportCampaignsHelper::get_all_post_types_tax_term();

		foreach ( $all_post_type_tax_term['post_types'] as $post_type_data ) {
			if ( $post_type_data['post_type'] === $post_type ) {
				if ( 'null' !== $taxonomy ) {
					foreach ( $post_type_data['taxonomies'] as $taxonomy_data ) {
						if ( $taxonomy_data['taxonomy_slug'] === $taxonomy ) {
							if ( 'null' !== $taxonomy_term ) {
								foreach ( $taxonomy_data['terms'] as $term_data ) {
									if ( $term_data['term_id'] === $taxonomy_term ) {
										return true;  // Valid post type, taxonomy, and term.
									}
								}
								return new \WP_Error( 'invalid_taxonomy_term', __( 'Invalid taxonomy term.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
							}
							return true;  // Taxonomy exists but no terms, still considered valid.
						}
					}
					return new \WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
				}
				return true;  // Post type exists but no taxonomies, still considered valid.
			}
		}

		return new \WP_Error( 'invalid_post_type', __( 'Invalid post type.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
	}


	/**
	 * Validate schedule settings.
	 *
	 * @param array $schedule_settings The schedule settings array.
	 * @return mixed True if valid, otherwise WP_Error.
	 */
	public function validate_schedule_settings( $schedule_settings ) {
		// Define valid schedule settings based on the new structure.
		$valid_frequencies = array( 'daily', 'weekly', 'monthly', 'hourly' );
		$valid_days        = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
	
		// Validate 'enabled' field.
		if ( ! isset( $schedule_settings['enabled'] ) ) {
			return new \WP_Error( 'invalid_enabled', __( 'Enabled must be set to "on" or "off".', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}
	
		// If 'enabled' is not 'on', return true without further validation.
		if ( 'on' !== $schedule_settings['enabled'] ) {
			return true;
		}

		// Validate 'frequency' field.
		if ( ! isset( $schedule_settings['frequency'] ) || ! in_array( $schedule_settings['frequency'], $valid_frequencies ) ) {
			return new \WP_Error( 'invalid_frequency', __( 'Frequency must be one of "daily", "weekly", "monthly", or "hourly".', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}
	
		// Validate 'specific_hour' field for hourly schedules.
		if ( 'hourly' === $schedule_settings['frequency'] ) {
			if ( ! isset( $schedule_settings['specific_hour'] ) || ! is_numeric( $schedule_settings['specific_hour'] ) || $schedule_settings['specific_hour'] < 0 || $schedule_settings['specific_hour'] > 24 ) {
				return new \WP_Error( 'invalid_specific_hour', __( 'Hour must be a number between 0 and 24 for hourly schedules.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
			}
		} elseif ( 'weekly' === $schedule_settings['frequency'] ) {
			// Validate 'day' field for weekly schedules.
			if ( ! isset( $schedule_settings['specific_day'] ) || ! in_array( $schedule_settings['specific_day'], $valid_days ) ) {
				return new \WP_Error( 'invalid_day', __( 'Day must be one of "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", or "sunday" for weekly schedules.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
			}
		} elseif ( isset( $schedule_settings['time'] ) ) {
			$valid_time = \DateTime::createFromFormat( 'H:i', $schedule_settings['time'] );
			if ( ! $valid_time ) {
				return new \WP_Error( 'invalid_time', __( 'Time must be in the format HH:MM.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
			}
		}
		return true;
	}
}
