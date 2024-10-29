<?php
/**
 * This File Contains the Helper Class for Import Campaigns.
 * 
 * @package Re-Campaign-Monitor
 * @subpackage ImportCampaigns
 * @since 1.0.0
 */

namespace ITCM_Campaign_Monitor\ImportCampaigns;

use CS_REST_Clients;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Import Campaigns Helper Class.
 *
 * Handles the helper functionality for Import Campaigns.
 *
 * @since      1.0.0
 * @package    ITCM_Campaign_Monitor
 * @subpackage ITCM_Campaign_Monitor/ImportCampaigns
 */
class ImportCampaignsHelper {
	
	/**
	 * Get all post types.
	 *
	 * @return array
	 */
	public static function get_all_post_types_tax_term() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$data       = array( 'post_types' => array() );
	
		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type->name, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) {
				continue;
			}
	
			$post_type_data = array(
				'post_type'  => $post_type->name,
				'taxonomies' => array(),
			);
	
			$taxonomies = get_object_taxonomies( $post_type->name, 'objects' );
	
			foreach ( $taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy->name, array( 'nav_menu', 'link_category', 'post_format' ) ) ) {
					continue;
				}
	
				$taxonomy_data = array(
					'taxonomy_slug' => $taxonomy->name,
					'taxonomy_name' => $taxonomy->labels->name,
					'terms'         => array(),
				);
	
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy->name,
						'hide_empty' => false,
					)
				);
	
				foreach ( $terms as $term ) {
					$term_data = array(
						'term_id'   => $term->term_id,
						'term_name' => $term->name,
					);
	
					$taxonomy_data['terms'][] = $term_data;
				}
	
				$post_type_data['taxonomies'][] = $taxonomy_data;
			}
	
			$data['post_types'][] = $post_type_data;
		}
	
		return $data;
	}
	
	/**
	 * Get all post statuses.
	 *
	 * @return array
	 */
	public static function get_all_post_statuses() {
		$post_statuses         = get_post_stati( array( 'internal' => false ), 'objects' );
		$data['post_statuses'] = wp_list_pluck( $post_statuses, 'label' );
		return $data;
	}

	/**
	 * Get all authors.
	 *
	 * @return array
	 */
	public static function get_all_authors() {
		$data['authors'] = get_users(
			array(
				'role'   => 'author',
				'fields' => array( 'ID', 'display_name' ),
			)
		);
		return $data;
	}

	/**
	 * Get the past imported campaigns.
	 * 
	 * @param array $params The parameters array.
	 * @return array
	 */
	public static function get_past_imported_campaigns( $params ) {
		$past_imported_posts_ids = get_posts(
			array(
				'post_type'   => sanitize_text_field( $params['post_type'] ),
				'post_status' => 'any',
				//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'  => array(
					array(
						'key'     => 'cm_campaign_id',
						'compare' => 'EXISTS',
					),
				),
				'fields'      => 'ids',
				'numberposts' => -1,
			) 
		);

		$output = array();
		foreach ( $past_imported_posts_ids as $post_id ) {
			$cm_campaign_id            = get_post_meta( $post_id, 'cm_campaign_id', true );
			$output[ $cm_campaign_id ] = $post_id;
		}
		return $output;
	}

	/**
	 * Filter the campaigns based on the import options.
	 * 
	 * @param array $campaigns The campaigns array.
	 * @param array $past_imported_campaigns The past imported campaigns array.
	 * @param array $import_option The parameters array.
	 * @return array
	 */
	public static function filter_campaigns( $campaigns, $past_imported_campaigns, $import_option ) {
		$output = array();
		switch ( $import_option ) {
			case 'both':
				foreach ( $campaigns as $status => $campaign ) {
					foreach ( $campaign as $cm ) {
						if ( array_key_exists( $cm->CampaignID, $past_imported_campaigns ) ) {
							$cm->wp_status  = 'existing';
							$cm->wp_post_id = $past_imported_campaigns[ $cm->CampaignID ];
						} else {
							$cm->wp_status = 'new';
						}
						$output[ $status ][] = $cm;
					}
				}
				break;
			case 'new':
				foreach ( $campaigns as $status => $campaign ) {
					foreach ( $campaign as $cm ) {
						if ( ! array_key_exists( $cm->CampaignID, $past_imported_campaigns ) ) {
							$cm->wp_status       = 'new';
							$output[ $status ][] = $cm;
						}
					}
				}
				break;
			case 'update':
				foreach ( $campaigns as $status => $campaign ) {
					foreach ( $campaign as $cm ) {
						if ( array_key_exists( $cm->CampaignID, $past_imported_campaigns ) ) {
							$cm->wp_status       = 'existing';
							$cm->wp_post_id      = $past_imported_campaigns[ $cm->CampaignID ];
							$output[ $status ][] = $cm;
						}
					}
				}
				break;
		}
		return $output;
	}

	/**
	 * Get all campaigns.
	 *
	 * @param array $credentials The credentials array.
	 * @param array $post_status The post status array.
	 * @return array
	 */
	public static function fetch_all_campaigns( $credentials, $post_status ) {
		$api_key                 = trim( $credentials['api_key'] );
		$client_id               = trim( $credentials['client_id'] );
		$campaign_monitor_client = new CS_REST_Clients( $client_id, array( 'api_key' => $api_key ) );
	
		$post_status_keys = array_keys( $post_status );
		$all_campaigns    = array();
	
		foreach ( $post_status_keys as $status ) {
			$result = self::fetch_campaigns_by_status( $campaign_monitor_client, $status );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$all_campaigns[ $status ] = $result;
		}
		return $all_campaigns;
	}

	/**
	 * Fetch campaigns by a specific status.
	 *
	 * @param CS_REST_Clients $client Campaign Monitor client.
	 * @param string          $status The campaign status.
	 * @return array|WP_Error
	 */
	public static function fetch_campaigns_by_status( $client, $status ) {
		if ( ! in_array( $status, array( 'scheduled', 'draft', 'published' ), true ) ) {
			return new \WP_Error( 'unknown_status', __( 'Unknown status.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		$campaigns = array();
		if ( 'published' === $status ) {
			$page     = 1;
			$per_page = 100;
			do {

				$result = self::get_campaigns_by_status( $client, $status, $page, $per_page );
				if ( is_wp_error( $result ) ) {
					return $result; // Early return on error.
				}

				$campaigns = array_merge( $campaigns, $result['campaigns'] );
				++$page;
			} while ( $page <= $result['NumberOfPages'] );
		} else {
			$result = self::get_campaigns_by_status( $client, $status );
			if ( is_wp_error( $result ) ) {
				return $result; // Early return on error.
			}
			$campaigns = $result['campaigns'];
		}

		return $campaigns;
	}

	/**
	 * Get campaigns by status from Campaign Monitor.
	 *
	 * @param CS_REST_Clients $client Campaign Monitor client.
	 * @param string          $status Campaign status.
	 * @param int             $page Optional. Page number (used only for published status).
	 * @param int             $per_page Optional. Number of campaigns per page (used only for published status).
	 * @return array|WP_Error
	 */
	public static function get_campaigns_by_status( $client, $status, $page = null, $per_page = null ) {
		switch ( $status ) {
			case 'scheduled':
				$result = $client->get_scheduled();
				break;
			case 'draft':
				$result = $client->get_drafts();
				break;
			case 'published':
				$result = $client->get_campaigns( null, $page, $per_page );
				break;
			default:
				return new \WP_Error( 'invalid_status', __( 'Invalid status provided.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}

		if ( $result->was_successful() ) {
			return array(
				'campaigns'     => ! empty( $result->response->Results ) ? $result->response->Results : $result->response,
				'NumberOfPages' => ! empty( $result->response->NumberOfPages ) ? $result->response->NumberOfPages : 1,
			);
		} else {
			// translators: %s: status of the campaigns.
			return new \WP_Error( "failed_to_get_{$status}", sprintf( __( 'Failed to get %s campaigns.', 'advanced-campaign-monitor-integration' ), $status ), array( 'status' => 400 ) );
		}
	}


	/**
	 * Include the WooCommerce action scheduler.
	 * 
	 * @return void
	 */
	public static function include_action_scheduler() {
		if ( ! class_exists( 'ActionScheduler' ) ) {
			require_once ITCM_CAMPAIGN_MONITOR_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		}
	}


	/**
	 * Set scheduled import.
	 * 
	 * @param array $params The parameters array.
	 * @return int|\WP_Error
	 */
	public static function schedule_import_campaigns( $params ) {
		$frequency = $params['schedule_settings']['frequency'];
		$timestamp = strtotime( 'now' );
	
		switch ( $frequency ) {
			case 'hourly':
				$hours_interval = $params['schedule_settings']['specific_hour'];
				$timestamp      = strtotime( 'now' ) + $hours_interval * 3600;
				$interval       = $hours_interval * 3600; // Convert hours to seconds.
				break;
			case 'daily':
				$specific_time = $params['schedule_settings']['time']; // Expected format: 'HH:MM'.
				$tomorrow      = gmdate( 'Y-m-d', strtotime( 'tomorrow' ) );
				$timestamp     = strtotime( "$tomorrow $specific_time UTC" );
				$interval      = DAY_IN_SECONDS; // Schedule daily.
				break;
			case 'weekly':
				$specific_day  = $params['schedule_settings']['specific_day']; // Expected format: 'Monday', 'Tuesday', etc.
				$specific_time = $params['schedule_settings']['time']; // Expected format: 'HH:MM'.
				$next_week     = strtotime( "next $specific_day $specific_time UTC" );
				// Ensure it's set to the next occurrence if today is the same as the specified day.
				if ( gmdate( 'l' ) === $specific_day && strtotime( "$specific_time UTC" ) > time() ) {
					$next_week = strtotime( "$specific_day $specific_time UTC" );
				}
				$timestamp = $next_week;
				$interval  = WEEK_IN_SECONDS; // Schedule weekly.
				break;
			default:
				return new \WP_Error( 'invalid_frequency', __( 'Invalid frequency.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		}
	
		$schedule_id = as_schedule_recurring_action(
			$timestamp,
			$interval,
			'itcm_import_campaigns',
			array( $params ),
			'itcm_import_campaigns_group'
		);
	
		if ( 0 === $schedule_id ) {
			return new \WP_Error( 'failed_to_schedule_import', __( 'Failed to schedule import.', 'advanced-campaign-monitor-integration' ), array( 'status' => 400 ) );
		} else {
			return $schedule_id;
		}
	}
}
