<?php
/**
 * Handles registering and deregistering crons
 */
namespace UCF\Critical_CSS\Includes\Cron {
	/**
	 * Function that registers the cron based on if the
	 * cron is enabled in the plugin settings.
	 * @author Jim Barnes
	 * @since 0.1.0
	 * @return void
	 */
	function register_cron() {
		$screen = get_current_screen();

		// Only do this logic if the save is coming from the settings page
		if ( $screen->id !== 'settings_page_ucf-critical-css' ) return;

		$schedule_cron  = get_option( 'enable_shared_css_cron', 'option' );
		$next_timestamp = wp_next_scheduled( 'ucfccss_critical_css_cron' );

		if ( $schedule_cron && ! $next_timestamp ) {
			wp_schedule_event( time(), 'hourly', 'ucfccss_critical_css_cron' );
		} else if ( ! $schedule_cron && $next_timestamp ) {
			wp_unschedule_event( $next_timestamp, 'ucfccss_critical_css_cron' );
		}
	}

	/**
	 * Function that deregisters the cron regardless
	 * of the setting. Meant to be used during the
	 * deactivation hook.
	 * @author Jim Barnes
	 * @since 0.1.0
	 * @return void
	 */
	function disable_cron() {
		$next_timestamp = wp_next_scheduled( 'ucfccss_critical_css_cron' );

		if ( $next_timestamp ) {
			wp_unschedule_event( $next_timestamp, 'ucfccss_critical_css_cron' );
		}
	}
}
