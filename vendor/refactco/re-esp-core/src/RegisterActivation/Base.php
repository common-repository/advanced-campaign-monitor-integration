<?php
namespace Refact\ESP_Core\RegisterActivation;

class Base {
	public function __construct( $file ) {
		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );
	}

	public function activate() {
		DatabaseTables::get_instance();
	}

	public function deactivate() {
		//
	}
}
