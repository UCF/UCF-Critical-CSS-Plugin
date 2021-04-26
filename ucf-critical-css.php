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
	use UCF\Critical_CSS\Includes\Cron;

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
	include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'includes/cron.php';


	/**
	 * Runs when the plugin is deactivated.
	 * @author Jim Barnes
	 * @since 0.1.0
	 * @return void
	 */
	function on_deactivate() {
		Cron\disable_cron();
	}

	register_deactivation_hook( UCF_CRITICAL_CSS__PLUGIN_FILE, __NAMESPACE__ . '\on_deactivate' );

	/**
	 * Main entry function for the plugin.
	 * All actions and filters should be registered here
	 * @author Jim Barnes
	 * @since 0.1.0
	 * @return void
	 */
	function plugin_init() {
		add_action( 'acf/init', array( 'UCF\Critical_CSS\Admin\Config', 'add_options_page' ), 10, 0 );
		add_action( 'acf/save_post', array( 'UCF\Critical_CSS\Admin\Config', 'clean_deferred_rules' ), 10, 1 );
		add_action( 'acf/save_post', 'UCF\Critical_CSS\Includes\Cron\register_cron', 10, 0  );

		// Register our dynamic filters and actions
		add_action( 'init', array( 'UCF\Critical_CSS\Admin\Actions', 'save_post_actions' ), 10, 0 );
		add_action( 'init', array( 'UCF\Critical_CSS\Admin\Actions', 'edit_term_actions' ), 10, 0 );

		add_action( 'rest_api_init', array( 'UCF\Critical_CSS\API\Critical_CSS_API', 'register_rest_routes' ) );

		if ( Deferred_Styles\enabled_globally() ) {
			add_action( 'wp_head', 'UCF\Critical_CSS\Includes\Critical_CSS\insert_in_head', 1 );
			add_action( 'style_loader_tag', 'UCF\Critical_CSS\Includes\Deferred_Styles\defer_enqueued_styles', 99, 4 );
		}

		add_action(
			'acf/init',
			array( 'UCF\Critical_CSS\Admin\Config', 'add_options_page_fields') , 10, 0 );

		add_filter(
			'acf/load_field/key=ucfccss_deferred_rules_post_type',
			array( 'UCF\Critical_CSS\Admin\Config', 'get_post_types_choices' ), 10, 1 );

		add_filter(
			'acf/load_field/key=ucfccss_deferred_rules_taxonomies',
			array( 'UCF\Critical_CSS\Admin\Config', 'get_taxonomies_choices' ), 10, 1 );

		add_filter(
			'acf/load_field/key=ucfccss_deferred_rules_templates',
			array( 'UCF\Critical_CSS\Admin\Config', 'get_templates_choices' ), 10, 1 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once UCF_CRITICAL_CSS__PLUGIN_DIR . 'includes/wp-cli.php';

			\WP_CLI::add_command( 'critical-css', 'UCF\Critical_CSS\Includes\CLI\CriticalCSSCommand' );
		}

		// Register the hook for the cron
		add_action( 'ucfccss_critical_css_cron', array( 'UCF\Critical_CSS\Admin\Utilities', 'update_shared_critical_css' ) );
	}

	add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin_init', 99, 0 );

}
