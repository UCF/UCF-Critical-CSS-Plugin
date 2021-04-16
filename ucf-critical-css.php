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

	use UCF\Critical_CSS\Includes\Deferred_Styles;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	define( 'UCF_CRITICAL_CSS__PLUGIN_URL', plugins_url( basename( dirname( __FILE__ ) ) ) );
	define( 'UCF_CRITICAL_CSS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'UCF_CRITICAL_CSS__PLUGIN_FILE', __FILE__ );
	define( 'UCF_CRITICAL_CSS__MAX_MSG_SIZE', 64000 );

	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'admin/config.php';
	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'admin/actions.php';
	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'admin/utils.php';

	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'api/api.php';

	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'includes/critical-css.php';
	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'includes/deferred-styles.php';

	/**
	 * Main entry function for the plugin.
	 * All actions and filters should be registered here
	 * @author Jim Barnes
	 * @since 0.1.0
	 * @return void
	 */
	function plugin_init() {
		add_action( 'init', array( 'UCF\Critical_CSS\Admin\Config', 'add_options_page' ), 20, 0 );

		// Register our dynamic filters and actions
		add_action( 'init', array( 'UCF\Critical_CSS\Admin\Actions', 'save_post_actions' ) );
		add_action( 'init', array( 'UCF\Critical_CSS\Admin\Actions', 'edit_term_actions' ) );

		add_action( 'rest_api_init', array( 'UCF\Critical_CSS\API\Critical_CSS_API', 'register_rest_routes' ) );

		if ( Deferred_Styles\enabled_globally() ) {
			add_action( 'wp_head', 'UCF\Critical_CSS\Includes\Critical_CSS\insert_in_head', 1 );
			add_action( 'style_loader_tag', 'UCF\Critical_CSS\Includes\Deferred_Styles\async_enqueued_styles', 99, 4 );
		}
	}

	add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin_init' );

}
