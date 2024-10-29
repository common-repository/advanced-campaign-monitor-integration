<?php

namespace Refact\ESP_Core\Abstracts;

use WP_Error;

abstract class Refact_ESP {
	protected $esp_name;

	public function __construct($esp_name) {
		$this->esp_name = $esp_name;
		$this->init();
	}

	abstract public function init(): void;
	abstract public function get_lists();
	abstract public function get_list( string $list_id );
	abstract public function get_subscriber( string $email, string $list_id = '');
	abstract public function update_subscriber( string $email, array $data, string $list_id = '' );
	abstract public function add_subscriber( string $email, array $data, string $list_id = '' );
	abstract public function map_data( $user, array $tasks, array $args = [] ): array;

	public function is_email( string $email ) {
		return is_email( urldecode($email) );
	}
	public function get_esp_name() {
		return $this->esp_name;
	}
}
