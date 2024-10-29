<?php

namespace Refact\ESP_Core\Handlers;

class TriggerTypes {
	private static ?TriggerTypes $instance = null;

	public static function get_instance(): ?TriggerTypes {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	public function get_wp_user_roles_trigger_type(): array {
		$roles = wp_roles()->roles;
		$trigger_type = [
			"value"         => 'wp_user_roles',
			"label"         => 'WP User Roles',
			"disabled"      => false,
			"trigger_name"  => "Role",
			"triggers"      => [],
			"children"      => []
		];
		foreach ( $roles as $role => $details ) {
			$trigger_type["triggers"][] = [
				"value" => $role,
				"label" => $details["name"]
			];
		}
		return $trigger_type;
	}

	public function get_wc_subscription_trigger_type(): array {
		$trigger_type = [
			"value"         => 'wc_subscription',
			"label"         => 'WooCommerce Subscription',
			"disabled"      => true,
			"trigger_name"  => "Status",
			"triggers"      => [],
			"children"      => []
		];
		$products = wc_get_products( array( 'type' => 'subscription' ) );
		foreach ( $products as $product ) {
			$trigger_type["children"][] = [
				"value" => $product->get_id(),
				"label" => $product->get_name()
			];
		}

		# get all available subscription statuses from WooCommerce Subscriptions and add them as triggers
		$subscription_statuses = wcs_get_subscription_statuses();
		foreach ( $subscription_statuses as $slug => $title ) {
			$trigger_type["triggers"][] = [
				"value" => $slug,
				"label" => $title
			];
		}

		return $trigger_type;
	}

	public function get_wc_membership_trigger_type(): array {
		$trigger_type = [
			"value"         => 'wc_membership',
			"label"         => 'WooCommerce Membership',
			"disabled"      => true,
			"trigger_name"  => "Status",
			"triggers"      => [],
			"children"      => []
		];
		$products = wc_get_products( array( 'type' => 'membership' ) );
		foreach ( $products as $product ) {
			$trigger_type["children"][] = [
				"value" => $product->get_id(),
				"label" => $product->get_name()
			];
		}

		# get all woocommerce membership statuses
		$membership_statuses = wc_memberships_get_user_membership_statuses();
		foreach ( $membership_statuses as $slug => $title ) {
			$trigger_type["triggers"][] = [
				"value" => $slug,
				"label" => $title
			];
		}

		return $trigger_type;
	}

	public function get_trigger_types(): array {
		// check each class exists and then add to trigger types
		$trigger_types = [];

		$trigger_types[] = $this->get_wp_user_roles_trigger_type();

		if ( class_exists( 'WC_Subscriptions' ) ) {
			$trigger_types[] = $this->get_wc_subscription_trigger_type();
		}
		if ( class_exists( 'WC_Memberships' ) ) {
			$trigger_types[] = $this->get_wc_membership_trigger_type();
		}

		return $trigger_types;
	}

	public function get_wp_fields(): array {
		$wp_fields = [
			[
				"value" => "user_email",
				"label" => "Email"
			],
			[
				"value" => "user_login",
				"label" => "Username"
			],
			[
				"value" => "user_nicename",
				"label" => "Nice Name"
			],
			[
				"value" => "user_url",
				"label" => "Website"
			],
			[
				"value" => "display_name",
				"label" => "Display Name"
			],
			[
				"value" => "first_name",
				"label" => "First Name"
			],
			[
				"value" => "last_name",
				"label" => "Last Name"
			],
			[
				"value" => "description",
				"label" => "Biographical Info"
			],
			[
				"value" => "user_registered",
				"label" => "Registration Date"
			],
			[
				"value" => "billing_first_name",
				"label" => "Billing First Name"
			],
			[
				"value" => "billing_last_name",
				"label" => "Billing Last Name"
			],
			[
				"value" => "billing_company",
				"label" => "Billing Company"
			],
			[
				"value" => "billing_address_1",
				"label" => "Billing Address 1"
			],
			[
				"value" => "billing_address_2",
				"label" => "Billing Address 2"
			],
			[
				"value" => "billing_city",
				"label" => "Billing City"
			],
			[
				"value" => "billing_state",
				"label" => "Billing State"
			],
			[
				"value" => "billing_postcode",
				"label" => "Billing Postcode"
			],
			[
				"value" => "billing_country",
				"label" => "Billing Country"
			]
		];

		return apply_filters( 're_esp_wp_fields', $wp_fields );
	}
}
