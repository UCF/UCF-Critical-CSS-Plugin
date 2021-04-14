<?php
namespace UCF\Critical_CSS\Admin {
	/**
	 * Utility functions for registering actions dynamically
	 */
	class Actions {
		/**
		 * Creates the save_post actions for the
		 * enabled post types.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function save_post_actions() {
			$enabled_post_types = get_field( 'allowed_post_types', 'option' );

			if ( ! $enabled_post_types ) return;

			foreach( $enabled_post_types as $post_type ) {
				add_action( "save_post_$post_type", array( __NAMESPACE__ . "\Utilities", 'on_save_post' ), 10, 2 );
			}
		}

		/**
		 * Creates the edit_term actions for the
		 * enabled taxonomies
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		 public static function edit_term_actions() {
			$enabled_taxonomies = get_field( 'allowed_taxonomies', 'option' );

			if ( ! $enabled_taxonomies ) return;

			foreach( $enabled_taxonomies as $tax ) {
				add_action( "create_$tax", array( __NAMESPACE__ . '\Utilities', 'on_edit_taxonomy' ), 10, 2 );
				add_action( "edit_$tax", array( __NAMESPACE__ . '\Utilities', 'on_edit_taxonomy' ), 10, 2 );
			}
		}
	}
}
