<?php
/**
 * Background process for importing fetched campaigns into WordPress.
 * 
 * @package ITCM_Campaign_Monitor
 * @subpackage Importcampaigns\BackgroundProcessing
 * @since 1.0.0
 */

namespace ITCM_Campaign_Monitor\ImportCampaigns\BackgroundProcessing;

use WP_Background_Process;

/**
 * Processes for importing fetched campaigns into WordPress.
 */
class ImportCampaignsProcess extends WP_Background_Process {

	/**
	 * The prefix for the background process.
	 * 
	 * @var string
	 */
	protected $prefix = 'ITCM_Campaign_Monitor';

	/**
	 * The batch size for the background process.
	 * 
	 * @var int Batch size
	 */
	protected $batch_size = 20;

	/**
	 * The action name for importing campaigns.
	 * 
	 * @var string
	 */
	protected $action = 'import_campaigns';

	/**
	 * Perform task with queued item.
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$cm_content_url = 'published' === $item['campaign_status'] ? $item['campaign']->WebVersionURL : $item['campaign']->PreviewURL;
		
		if ( ! isset( $item['campaign']->html_content ) ) {
			$item['campaign']->html_content = $this->fetch_campaign_content( $cm_content_url );
			return $item;
		} else {
			// Import the campaign.
			$this->import_campaign( $item );
			return false;
		}
	}

	/**
	 * Complete processing.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		delete_transient( 'itcm_total_campaigns' );
		parent::complete();
	}

	/**
	 * Fetch the campaign content.
	 * 
	 * @param string $url The URL of the campaign.
	 * 
	 * @return string
	 */
	protected function fetch_campaign_content( $url ) {
		//phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response = \wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return 'not found';
		}
		$body = wp_remote_retrieve_body( $response );
		$body = apply_filters( 'itcm_campaign_monitor_campaign_content', $body );
		return $body;
	}

	/**
	 * Import the campaign.
	 * 
	 * @param object $item The campaign object.
	 */
	protected function import_campaign( $item ) {
		$wp_post_args = array(
			'post_title'   => sanitize_text_field( $item['campaign']->Subject ),
			'post_slug'    => sanitize_title( $item['campaign']->Name ),
			'post_content' => $item['campaign']->html_content,
			'post_status'  => sanitize_text_field( $item['params']['post_status'][ $item['campaign_status'] ] ),
			'post_type'    => sanitize_text_field( $item['params']['post_type'] ),
		);

		// Set the post date.
		if ( ! empty( $item['campaign']->SentDate ) ) {
			$wp_post_args['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $item['campaign']->SentDate ) );
		}

		// Set the post taxonomy and term.
		if ( ! empty( $item['params']['taxonomy'] ) && ! empty( $item['params']['taxonomy_term'] ) ) {
			$term = term_exists( intval( $item['params']['taxonomy_term'] ), $item['params']['taxonomy'] );

			if ( $term ) {
				$wp_post_args['tax_input'] = array(
					$item['params']['taxonomy'] => array( $term['term_id'] ),
				);
			}
		}

		// Add campaign tags as post categories or tags.
		if ( ! empty( $item['campaign']->Tags ) ) {
			$tags = $item['campaign']->Tags;

			if ( 'category' === $item['params']['import_cm_tags_as'] ) {
				$tag_ids = array();
				foreach ( $tags as $tag ) {
					$term = term_exists( $tag, 'category' );
					if ( ! $term ) {
						$term = wp_insert_term( $tag, 'category' );
					}
					$tag_ids[] = $term['term_id'];
				}
				$wp_post_args['post_category'] = $tag_ids;
			} elseif ( 'post_tag' === $item['params']['import_cm_tags_as'] ) {
				$wp_post_args['tags_input'] = $tags;
			}
		}

		// Set post author.
		if ( ! empty( $item['params']['author'] ) ) {
			$wp_post_args['post_author'] = intval( $item['params']['author'] );
		}

		$cm_content_url = 'published' === $item['campaign_status'] ? $item['campaign']->WebVersionURL : $item['campaign']->PreviewURL;
		// Set post meta.
		$wp_post_args['meta_input'] = array(
			'cm_campaign_id'     => $item['campaign']->CampaignID,
			'cm_web_version_url' => $cm_content_url,
		);

		// Base on the campaign wp_status we will decide to update or insert the post.
		if ( 'existing' === $item['campaign']->wp_status ) {
			$wp_post_args['ID'] = $item['campaign']->wp_post_id;
			// Update the post.
			wp_update_post( $wp_post_args );
		} else {
			// Insert the post.
			wp_insert_post( $wp_post_args );
		}
	}
}
