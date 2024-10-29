<?php
namespace Refact\ESP_Core\API;

use Refact\ESP_Core\Abstracts\Refact_ESP;
use Refact\ESP_Core\Handlers\TriggerTypes;
use WP_REST_Request;

class RegisterEndpoints {

	public string $esp_name;

	public Refact_ESP $esp;

	public function __construct( Refact_ESP $esp ) {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		$this->esp      = $esp;
		$this->esp_name = $esp->get_esp_name();
	}

	// singleton
	public static function get_instance( Refact_ESP $esp ): ?RegisterEndpoints {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static( $esp );
		}

		return $instance;
	}

	public function register_endpoints() {
		register_rest_route( 're-esp/v1/' . $this->esp_name, '/lists', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_esp_lists' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/lists/(?P<list_id>[a-zA-Z0-9-]+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_esp_list' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/subscriber/(?P<email>.+)(/(?P<list_id>[a-zA-Z0-9-]+))?', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_esp_subscriber' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/subscriber/(?P<email>.+)(/(?P<list_id>[a-zA-Z0-9-]+))?', array(
			'methods'  => 'PUT',
			'callback' => array( $this, 'add_esp_subscriber' ), // update subscriber
		) );

		register_rest_route( 're-esp/v1' . $this->esp_name, '/subscriber/(?P<email>.+)(/(?P<list_id>[a-zA-Z0-9-]+))?', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'add_esp_subscriber' ), // add subscriber
		) );


		register_rest_route( 're-esp/v1/' . $this->esp_name, '/rules', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_esp_rules' ),
		) );


		register_rest_route( 're-esp/v1/' . $this->esp_name, '/rules/(?P<rule_id>[a-zA-Z0-9-]+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_esp_rule' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/rules', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'add_esp_rule' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/rules/(?P<rule_id>[a-zA-Z0-9-]+)', array(
			'methods'  => 'PUT',
			'callback' => array( $this, 'update_esp_rule' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/rules/(?P<rule_id>[a-zA-Z0-9-]+)', array(
			'methods'  => 'DELETE',
			'callback' => array( $this, 'delete_esp_rule' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/triggers', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_trigger_types' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/triggers/(?P<trigger_type>[a-zA-Z0-9-_]+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_trigger_type' ),
		) );

		register_rest_route( 're-esp/v1/' . $this->esp_name, '/wp-fields', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_wp_fields' ),
		) );
	}

	public function get_esp_lists() {
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_lists_endpoint",[], $this->esp  )
		);
	}

	public function get_esp_list(WP_REST_Request $request) {
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_list_endpoint",[], $this->esp, $request  )
		);
	}

	public function get_esp_subscriber(WP_REST_Request $request) {
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_subscriber_endpoint",[], $this->esp, $request  )
		);
	}

	public function get_esp_rules(WP_REST_Request $request) {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_rules';
		$rules = $wpdb->get_results("SELECT * FROM $table_name WHERE esp_name = '{$this->esp_name}' ORDER BY id DESC");
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_rules_endpoint", $rules, $request  )
		);
	}

	public function get_esp_rule(WP_REST_Request $request) {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_rules';
		$rule_id = $request->get_param('rule_id');
		$rule = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND esp_name = %s", $rule_id, $this->esp_name)
		);
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_rule_endpoint", $rule ?? '', $request  )
		);
	}

	public function add_esp_rule(WP_REST_Request $request) {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_rules';
		$params = $request->get_params();
		$RulesModel = \Refact\ESP_Core\Models\Rules::get_instance($this->esp_name);
		$result = $RulesModel->add_rule($params);
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_add_rule_endpoint", $result, $request  )
		);
	}

	public function update_esp_rule(WP_REST_Request $request) {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_rules';
		$params = $request->get_params();

		// check if rule_id exists
		if(!isset($params['rule_id'])) {
			return rest_ensure_response(
				apply_filters( "re_esp_{$this->esp_name}_update_rule_endpoint", new \WP_Error('400', 'Rule ID is required'), $request  )
			);
		}

		$RulesModel = \Refact\ESP_Core\Models\Rules::get_instance($this->esp_name);
		$result = $RulesModel->update_rule($params['rule_id'], $params);
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_update_rule_endpoint", $result, $request  )
		);
	}

	public function delete_esp_rule(WP_REST_Request $request) {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_rules';
		$params = $request->get_params();

		// check if rule_id exists
		if(!isset($params['rule_id'])) {
			return rest_ensure_response(
				apply_filters( "re_esp_{$this->esp_name}_delete_rule_endpoint", new \WP_Error('400', 'Rule ID is required'), $request  )
			);
		}

		$RulesModel = \Refact\ESP_Core\Models\Rules::get_instance($this->esp_name);
		$result = $RulesModel->delete_rule($params['rule_id']);
		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_delete_rule_endpoint", $result, $request  )
		);
	}

	public function get_trigger_types(WP_REST_Request $request) {
		$trigger_types = TriggerTypes::get_instance()->get_trigger_types();

		$result = apply_filters("re_esp_{$this->esp_name}_trigger_types", $trigger_types);

		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_trigger_types_endpoint", $result, $request  )
		);
	}

	public function get_trigger_type(WP_REST_Request $request) {

		$trigger_type = $request->get_param('trigger_type');

		$result = get_trigger_type_targets($trigger_type);

		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_trigger_type_endpoint", $result, $request  )
		);
	}

	public function get_wp_fields(WP_REST_Request $request) {
		$trigger_types = TriggerTypes::get_instance()->get_wp_fields();

		$result = apply_filters("re_esp_{$this->esp_name}_wp_fields", $trigger_types);

		return rest_ensure_response(
			apply_filters( "re_esp_{$this->esp_name}_get_wp_fields_endpoint", $result, $request  )
		);
	}
}


