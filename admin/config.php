<?php
namespace UCF\Critical_CSS\Admin {
	/**
	 * Defines the plugin settings
	 */
	class Config {
		public static function add_options_page() {
			if ( function_exists( 'acf_add_options_sub_page' ) ) {
				$option_page = acf_add_options_page( array(
					'page_title'  => __( 'UCF Critical CSS Settings' ),
					'menu_title'  => __( 'UCF Critical CSS' ),
					'menu_slug'   => 'ucf-critical-css',
					'parent_slug' => 'options-general.php',
					'capability'  => 'manage_options',
					'redirect'    => false
				) );

				self::add_options_page_fields();

			} else {
				add_action( 'admin_notices', array( __NAMESPACE__ . '\Config', 'no_acf_admin_notice' ) );
			}
		}

		public static function add_options_page_fields() {
			if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

			$fields = array();

			/**
			 * General Fields
			 */
			$fields[] = array(
				'key'           => 'ucfccss_general_settings_tab',
				'label'         => 'General Settings',
				'type'          => 'tab'
			);

			$fields[] = array(
				'key'           => 'ucfccss_enable_deferred_styles_global',
				'label'         => 'Enable Deferred Styles',
				'name'          => 'enable_deferred_styles_global',
				'type'          => 'true_false',
				'instructions'  => 'When enabled, all styles will be deferred whenever critical CSS is present on a post/page or if the style has been whitelisted.',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => 'Enabled',
				'ui_off_text'   => 'Disabled'
			);

			$fields[] = array(
				'key'           => 'ucfccss_allowed_post_types',
				'label'         => 'Allowed Post Types',
				'name'          => 'allowed_post_types',
				'type'          => 'select',
				'instructions'  => 'Choose the post types for which styles should be deferred.',
				'choices'       => self::post_types_as_options(),
				'allow_null'    => 0,
				'multiple'      => 1,
				'ui'            => 1,
				'ajax'          => 1
			);

			$fields[] = array(
				'key'           => 'ucfccss_allowed_taxonomies',
				'label'         => 'Allowed Taxonomies',
				'name'          => 'allowed_taxonomies',
				'type'          => 'select',
				'instructions'  => 'Choose the taxonomies that allow critical CSS generation.',
				'choices'       => self::taxonomies_as_options(),
				'allow_null'    => 0,
				'multiple'      => 1,
				'ui'            => 1,
				'ajax'          => 1
			);

			$fields[] = array(
				'key'           => 'ucfccss_deferred_exceptions',
				'label'         => 'Deferred Exceptions',
				'name'          => 'deferred_exceptions',
				'type'          => 'textarea',
				'instructions'  => 'Enter the handles of each stylesheet which should not be deferred, one handle per line.'
			);

			/**
			 * Critical CSS Generation Fields
			 */
			$fields[] = array(
				'key'           => 'ucfccss_critical_css_tab',
				'label'         => 'Critical CSS Generation',
				'type'          => 'tab'
			);

			$fields[] = array(
				'key'           => 'ucfccss_enable_critical_css_generation',
				'label'         => 'Enable Critical CSS Generation',
				'name'          => 'enable_critical_css_generation',
				'type'          => 'true_false',
				'instructions'  => 'When enabled, this plugin will automatically generate critical CSS for eligible posts automatically.',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => 'Enabled',
				'ui_off_text'   => 'Disabled'
			);

			$fields[] = array(
				'key'           => 'ucfccss_critical_css_service_url',
				'label'         => 'Critical CSS Service URL',
				'name'          => 'critical_css_service_url',
				'type'          => 'url',
				'instructions'  => 'Enter the URL of the critical CSS service.'
			);

			$fields[] = array(
				'key'           => 'ucfccss_excluded_css_selectors',
				'label'         => 'Excluded CSS Selectors',
				'name'          => 'excluded_css_selectors',
				'type'          => 'textarea',
				'instructions'  => 'WE NEED SOME DETAILED INSTRUCTIONS HERE',
				'default_value' => 'style#critical-css
link[rel=\'stylesheet\'][href^=\'//cloud.typography.com/\']'
			);

			/**
			 * Define the sub fields for the dimension repeater
			 */
			$dimension_sub_fields = array();

			$dimension_sub_fields[] = array(
				'key'           => 'ucfccss_dimensions_width',
				'label'         => 'Width',
				'name'          => 'width',
				'type'          => 'number',
				'instructions'  => 'The width of the viewport.',
				'required'      => 1
			);

			$dimension_sub_fields[] = array(
				'key'           => 'ucfccss_dimensions_height',
				'label'         => 'Height',
				'name'          => 'height',
				'type'          => 'number',
				'instructions'  => 'The height of the viewport.',
				'required'      => 1
			);

			/**
			 * The dimensions repeater
			 */
			$fields[] = array(
				'key'           => 'ucfccss_critical_css_dimensions',
				'label'         => 'Critical CSS Dimensions',
				'name'          => 'critical_css_dimensions',
				'type'          => 'repeater',
				'instructions'  => 'Define the dimensions that Critical should use when generating the critical CSS. The dimensions should correspond with the common viewports based on the theme.',
				'required'      => 1,
				'collapsed'     => 'ucfccss_dimensions_width',
				'min'           => 1,
				'max'           => 10,
				'layout'        => 'table',
				'button_label'  => 'Add Dimension',
				'sub_fields'    => $dimension_sub_fields
			);

			$group = array(
				'key'      => 'ucfccss_settings_fields',
				'title'    => 'UCF Critical CSS Settings Fields',
				'fields'   => $fields,
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'ucf-critical-css'
						)
					)
				),
				'style'    => 'seamless'
			);

			acf_add_local_field_group( $group );
		}

		/**
		 * Returns registered post_types as a list
		 * of options for an ACF select field.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return array
		 */
		public static function post_types_as_options() {
			$retval = array();

			$args = array(
				'public' => true
			);

			$post_types = get_post_types( $args, 'objects' );

			foreach( $post_types as $post_type ) {
				$retval[$post_type->name] = $post_type->label;
			}

			return $retval;
		}

		/**
		 * Returns registered taxonomies as a list
		 * of options for an ACF select field.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return array
		 */
		public static function taxonomies_as_options() {
			$retval = array();

			$args = array(
				'public' => true
			);

			$taxonomies = get_taxonomies( $args, 'objects' );

			foreach( $taxonomies as $tax ) {
				$retval[$tax->name] = $tax->label;
			}

			return $retval;
		}

		/**
		 * Admin notice to display when ACF is not installed
		 * and enabled.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function no_acf_admin_notice() {
			ob_start();
		?>
			<div class="notice notice-error is-dismissible">
				<p>The UCF Critical CSS Plugin requires ACF Pro. Please, install the plugin.</p>
			</div>
		<?php
			echo ob_get_clean();
		}
	}
}
