<?php
/*
Plugin Name: UCF Critical CSS Plugin
Description: Plugin that allows for critical CSS and deferred styles and supports automatic generation via an external service.
Version: 0.1.0
Author: UCF Web Communications
License: GPL3
GitHub Plugin URI: UCF/UCF-Critical-CSS-Plugin
*/

namespace UCF\Critical_CSS {

	use UCF\Critical_CSS\Admin;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	define( 'UCF_CRITICAL_CSS__PLUGIN_URL', plugins_url( basename( dirname( __FILE__ ) ) ) );
	define( 'UCF_CRITICAL_CSS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'UCF_CRITICAL_CSS__PLUGIN_FILE', __FILE__ );

	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'admin/config.php';

	function plugin_activation() {
		Admin\Config::add_options();
	}

	register_activation_hook( UCF_CRITICAL_CSS__PLUGIN_FILE, __NAMESPACE__ . '\plugin_activation' );

	function plugin_deactivation() {
		Admin\Config::delete_options();
	}

	register_deactivation_hook( UCF_CRITICAL_CSS__PLUGIN_FILE, __NAMESPACE__ . '\plugin_deactivation' );

	function plugin_init() {
		add_action( 'admin_init', array( 'UCF\Critical_CSS\Admin\Config', 'settings_init' ) );
		add_action( 'admin_menu', array( 'UCF\Critical_CSS\Admin\Config', 'add_options_page' ) );
		Admin\Config::add_option_formatting_filters();
	}

	add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin_init' );
}
