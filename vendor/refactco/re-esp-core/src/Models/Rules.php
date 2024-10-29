<?php
namespace Refact\ESP_Core\Models;

use wpdb;

class Rules {
	private static ?Rules $instance = null;
	private string $prefix;
	private wpdb $wpdb;
	/**
	 * @var string
	 */
	private string $esp_name;
	private string $rules_table;


	public function __construct($esp_name) {
		global $wpdb;
		$this->prefix = $wpdb->prefix;
		$this->wpdb  = $wpdb;
		$this->esp_name = $esp_name;

		$this->rules_table = $this->prefix . 're_esp_rules';
	}

	public static function get_instance( $esp_name ): ?Rules {
		if ( null === static::$instance ) {
			static::$instance = new static( $esp_name );
		}
		return static::$instance;
	}

	public function get_rules() {
		return $this->wpdb->get_results(
			$this->wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE esp_name = %s",  $this->esp_name  )
			, ARRAY_A
		);
	}

	public function get_rule( $rule_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE id = %d AND esp_name = %s", $rule_id, $this->esp_name)
			, ARRAY_A
		);
	}

	public function get_rules_by_trigger($trigger_object, $trigger_condition, $integration = false ) {
		if($integration) {
			return $this->wpdb->get_results(
				$this->wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE esp_name = %s AND integration = %s AND trigger_object = %s AND trigger_condition = %s", $this->esp_name, $integration, $trigger_object, $trigger_condition)
				, ARRAY_A
			);
		} else {
			return $this->wpdb->get_results(
				$this->wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE esp_name = %s AND trigger_object = %s AND trigger_condition = %s", $this->esp_name, $trigger_object, $trigger_condition)
				, ARRAY_A
			);
		}

	}

	public function is_rule_exists( $data ): bool {
		$rule = $this->wpdb->get_row(
			$this->wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE esp_name = %s AND trigger_object = %s AND trigger_condition = %s AND list_id = %s", $this->esp_name, $data['trigger_object'], $data['trigger_condition'], $data['list_id'])
			, ARRAY_A
		);
		return ! empty( $rule );
	}

	public function add_rule( $data ) {
		// check if all required fields are present in data and not empty
		foreach ( $this->get_required_fields() as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new \WP_Error( 'missing_required_field', __( 'Missing required field: ' . $field, 'refact' ) );
			}
		}

		// remove any fields that are not in the table
		$columns = $this->get_columns();
		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $columns ) ) {
				unset( $data[ $key ] );
			}
		}

		// TODO: check if list exists

		if ( $this->is_rule_exists( $data ) ) {
			return new \WP_Error( 'rule_exists', __( 'Rule already exists', 'refact' ) );
		}

		// add esp_name to data
		$data['esp_name'] = $this->esp_name;

		// validate the type of data for each column
		foreach ( $data as $key => $value ) {
			// if key is condition or field_mappings json_encode value
			if ( $key === 'tasks' ) {
				$data[ $key ] = json_encode( $value );
			}
		}

		// insert rule
		$result = $this->wpdb->insert(
			$this->rules_table,
			$data,
			$this->generate_format_array($data)
		);
		// get error
		$error = $this->wpdb->last_error;

		if ( ! $result ) {
			return new \WP_Error( 'rule_insert_failed', __( 'Rule insert failed', 'refact' ) );
		}

		return true;
	}

	public function update_rule( $rule_id, $data ) {
		// check if all required fields are present in data and not empty
		$required_fields = $this->get_required_fields();
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new \WP_Error( 'missing_required_field', __( 'Missing required field', 'refact' ) );
			}
		}

		// remove any fields that are not in the table
		$columns = $this->get_columns();
		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $columns ) ) {
				unset( $data[ $key ] );
			}
		}

		$existing_rule = $this->wpdb->get_row(
			$this->wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE id = %d AND esp_name = %s", $rule_id, $this->esp_name)
			, ARRAY_A
		);

		// if rule exists, then update otherwise return error
		if ( empty( $existing_rule ) ) {
			return new \WP_Error( 'rule_not_found', __( 'Rule not found', 'refact' ) );
		}

		// validate the type of data for each column
		foreach ( $data as $key => $value ) {
			if ( $key === 'tasks' ) {
				$data[ $key ] = json_encode( $value );
			}
		}

		// update rule
		$result = $this->wpdb->update(
			$this->rules_table,
			$data,
			[ 'id' => $rule_id ],
			$this->generate_format_array($data),
			'%d'
		);

		if ( ! $result ) {
			return new \WP_Error( 'rule_update_failed', __( 'Rule update failed', 'refact' ) );
		}

		return true;
	}

	public function delete_rule( $rule_id ) {
		$result = $this->wpdb->delete( $this->rules_table,
			[
				'id' => $rule_id,
				'esp_name' => $this->esp_name
			],
			[
				'%d',
				'%s'
			]
		);

		if ( ! $result ) {
			return new \WP_Error( 'rule_delete_failed', __( 'Rule delete failed', 'refact' ) );
		}
		return true;
	}

	// table columns and writable fields
	public function get_columns(): array {
		return [
			'id' => '%d',
			'integration' => '%s',
			'trigger_object' => '%s',
			'trigger_condition' => '%s',
			'esp_name' => '%s',
			'list_id' => '%s',
			'tasks' => '%s',
			'status' => '%s'
		];
	}

	protected function generate_format_array($data): array {
		$format = [];
		$columns = $this->get_columns();
		foreach ( $data as $key => $value ) {
			$format[] = $columns[$key];
		}
		return $format;
	}

	public function get_required_fields(): array {
		return ['integration', 'trigger_object', 'trigger_condition', 'esp_name', 'list_id', 'tasks'];
	}

}
