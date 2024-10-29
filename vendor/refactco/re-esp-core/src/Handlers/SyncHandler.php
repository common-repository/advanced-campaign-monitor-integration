<?php
namespace Refact\ESP_Core\Handlers;

use Refact\ESP_Core\Abstracts\Refact_ESP;
use Refact\ESP_Core\Models\Rules;

class SyncHandler {
	/**
	 * @var mixed
	 */
	private $esp;
	private ?Rules $rules;

	private static ?SyncHandler $instance = null;

	public static function get_instance( Refact_ESP $esp ): ?SyncHandler {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static( $esp );
		}
		return $instance;
	}

	public function __construct(Refact_ESP $esp) {
		$this->esp = $esp;
		$this->rules = Rules::get_instance($esp->get_esp_name());
		$this->init();
	}

	public function init() {
		add_action( 'profile_update', [$this, "sync_user"], 10, 2 );

		// subscription status update
		add_action("woocommerce_subscription_status_updated", [$this, "sync_subscription_status"], 10, 3);
		// membership status update
		add_action("wc_memberships_user_membership_status_changed", [$this, "sync_membership_status"], 10, 3);
	}

	public function sync_user($user_id, $old_user_data) {
		$user = get_user_by("ID", $user_id);
		$email = $user->user_email;
		$user_roles = $user->roles;
		if(!is_array($user_roles)) {
			return;
		}

		array_map(function($role) use ($email, $user) {
			$rules = $this->rules->get_rules_by_trigger('wp_user_roles', $role);
			array_map(function($rule) use ($email, $user) {
				$this->sync($user, $rule['tasks'], $rule['list_id']);
			}, $rules);
		}, $user_roles);
	}

	public function sync_subscription_status($subscription, $new_status, $old_status) {
		$user_id    = $subscription->get_user_id();
		$user       = get_user_by( 'ID', $user_id );
		$email      = $user->user_email;
		$subscription_products = $subscription->get_items();

		foreach($subscription_products as $item_id => $item) :
			$product = $item->get_product();
			$subscription_product_id = $product->get_id();

			$rules = $this->rules->get_rules_by_trigger($subscription_product_id,'wc-'.$new_status, 'wc_subscription');
			array_map(function($rule) use ($user, $subscription) {
				$this->sync($user, $rule['tasks'], $rule['list_id']);
			}, $rules);
		endforeach;

	}

	public function sync($user, $tasks, $list_id = '') {
		$user_id = $user->ID;
		$email = $user->user_email;
		$tasks = json_decode($tasks, JSON_OBJECT_AS_ARRAY);
		$data = $this->esp->map_data($user, $tasks);

		$this->esp->add_subscriber($email, $data, $list_id);
	}
}
