<?php
/**
 * Utility command for refreshing critical CSS
 */
namespace UCF\Critical_CSS\Includes\CLI {
	use UCF\Critical_CSS\Admin;

	/**
	 * WP CLI Command that can refresh Critical CSS
	 * for shared or individual values.
	 * @author Jim Barnes
	 * @since 0.1.0
	 */
	class CriticalCSSCommand {
		/**
		 * The method that is invoked when
		 * the command is called.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $args The argument array
		 * @return void
		 */
		public function __invoke( $args ) {
			Admin\Utilities::update_shared_critical_css();
		}
	}
}
