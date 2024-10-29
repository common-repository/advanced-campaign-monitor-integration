<?php
namespace Refact\ESP_Core\RegisterActivation;

class DatabaseTables {

	static ?DatabaseTables $instance = null;

	public function __construct() {
		$this->create_tables();
	}

	public function create_tables() {
		$this->create_integration_rules_table();
		$this->create_integration_logs_table();
	}

	// singleton
	public static function get_instance(): ?DatabaseTables {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	public function drop_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_integration_rules';
		$wpdb->query("DROP TABLE IF EXISTS $table_name");
		$table_name = $wpdb->prefix . 're_integration_logs';
		$wpdb->query("DROP TABLE IF EXISTS $table_name");
	}

	public function create_integration_rules_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_rules';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id INT PRIMARY KEY AUTO_INCREMENT,
			integration VARCHAR(255) NOT NULL,
    		trigger_object VARCHAR(255) NOT NULL,
		    trigger_condition VARCHAR(255) NOT NULL,
		    esp_name VARCHAR(255) NOT NULL,
		    list_id VARCHAR(255) NOT NULL,
		    tasks TEXT DEFAULT NULL,
		    status VARCHAR(255) DEFAULT 'active'
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function create_integration_logs_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 're_esp_logs';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id INT PRIMARY KEY AUTO_INCREMENT,
			esp_name VARCHAR(255) NOT NULL,
		    integration_rule_id INT NOT NULL,
		    user_id INT NULL,
		    user_email VARCHAR(255) NOT NULL,
		    error TEXT DEFAULT NULL,
    		retry VARCHAR(255) DEFAULT 'no',
    		retry_count INT DEFAULT 0,
		    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    		update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}


}
