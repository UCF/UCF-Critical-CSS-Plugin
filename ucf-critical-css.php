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

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	define( 'UCF_CRITICAL_CSS__PLUGIN_URL', plugins_url( basename( dirname( __FILE__ ) ) ) );
	define( 'UCF_CRITICAL_CSS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'UCF_CRITICAL_CSS__PLUGIN_FILE', __FILE__ );

	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'admin/config.php';

	function plugin_init() {
		add_action( 'init', array( 'UCF\Critical_CSS\Admin\Config', 'add_options_page' ), 20, 0 );
	}

	add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin_init' );
}
