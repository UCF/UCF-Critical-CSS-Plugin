<?php
namespace UCF\Critical_CSS\Admin {
	/**
	 * Defines the plugin settings
	 */
	class Config {
		/**
		 * Adds the options page for the critical CSS settings
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
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

			} else {
				add_action( 'admin_notices', array( __NAMESPACE__ . '\Config', 'no_acf_admin_notice' ) );
			}
		}

		/**
		 * Registers the ACF fields for the
		 * Critical CSS options page
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function add_options_page_fields() {
			if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

			$fields = array();

			/**
			 * General Fields
			 */
			$fields[] = array(
				'key'           => 'ucfccss_general_settings_tab',
				'name'          => '',
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

			// Define the subfields for the deferment rules
			$deferred_rules_subfields = array();

			$deferred_rules_subfields[] = array(
				'key'           => 'ucfccss_deferred_rules_rule_type',
				'label'         => 'Rule Type',
				'name'          => 'rule_type',
				'type'          => 'select',
				'instructions'  => 'Select the type of rule to create',
				'choices'       => array(
					'individual' => 'Individual Page CSS',
					'shared'     => 'Shared Template Critical CSS'
				),
				'allow_null'    => 0,
				'multiple'      => 0
			);

			$deferred_rules_subfields[] = array(
				'key'           => 'ucfccss_deferred_rules_object_type',
				'label'         => 'Object Type',
				'name'          => 'object_type',
				'type'          => 'select',
				'instructions'  => 'Select the object type to apply this rule to',
				'choices'       => array(
					'post_type' => 'Post Type',
					'taxonomy'  => 'Taxonomy',
					'template'  => 'Template'
				),
				'allow_null'    => 0,
				'multiple'      => 0
			);

			$deferred_rules_subfields[] = array(
				'key'               => 'ucfccss_deferred_rules_post_type',
				'label'             => 'Post Types',
				'name'              => 'post_types',
				'type'              => 'select',
				'instructions'      => 'Choose the post types to apply this rule to',
				'choices'           => array(),
				'default_value'     => false,
				'allow_null'        => 0,
				'multiple'          => 1,
				'ui'                => 1,
				'ajax'              => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'ucfccss_deferred_rules_object_type',
							'operator' => '==',
							'value'    => 'post_type'
						)
					)
				)
			);

			$deferred_rules_subfields[] = array(
				'key'               => 'ucfccss_deferred_rules_taxonomies',
				'label'             => 'Taxonomies',
				'name'              => 'taxonomies',
				'type'              => 'select',
				'instructions'      => 'Choose the taxonomies to apply this rule to',
				'default_value'     => false,
				'choices'           => array(),
				'default_value'     => false,
				'allow_null'        => 0,
				'multiple'          => 1,
				'ui'                => 1,
				'ajax'              => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'ucfccss_deferred_rules_object_type',
							'operator' => '==',
							'value'    => 'taxonomy'
						)
					)
				)
			);

			$deferred_rules_subfields[] = array(
				'key'               => 'ucfccss_deferred_rules_templates',
				'label'             => 'Templates',
				'name'              => 'templates',
				'type'              => 'select',
				'instructions'      => 'Choose the templates to apply this rule to',
				'default_value'     => false,
				'choices'           => array(),
				'allow_null'        => 0,
				'multiple'          => 1,
				'ui'                => 1,
				'ajax'              => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'ucfccss_deferred_rules_object_type',
							'operator' => '==',
							'value'    => 'template'
						)
					)
				)
			);

			$fields[] = array(
				'key'           => 'ucfccss_deferred_rules',
				'label'         => 'Deferred Rules',
				'name'          => 'ucfccss_deferred_rules',
				'type'          => 'repeater',
				'instructions'  => 'The following rules determine when CSS will be deferred and critical CSS generated and inserted, when that feature is active.',
				'sub_fields'    => $deferred_rules_subfields,
				'collapsed'     => 'ucfccss_deferred_rules_rule_type',
				'min'           => 1,
				'max'           => 10,
				'layout'        => 'row',
				'button_label'  => 'Add Rule',
				''
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
				'name'          => '',
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
				'key'           => 'ucfccss_critical_css_service_key',
				'label'         => 'Critical CSS Service API Key',
				'name'          => 'critical_css_service_key',
				'type'          => 'password',
				'instructions'  => 'Enter the Service API Key for the API Endpoint'
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

			$fields[] = array(
				'key'           => 'ucfccss_shared_css_expiration',
				'label'         => 'Shared Critical CSS Expiration (Minutes)',
				'name'          => 'shared_css_expiration',
				'type'          => 'number',
				'instructions'  => 'The amount of time shared Critical CSS should be cached for in minutes.',
				'default_value' => 1440 // One day
			);

			$fields[] = array(
				'key'           => 'ucfccss_enable_shared_css_cron',
				'label'         => 'Enable Shared CSS Cron',
				'name'          => 'enable_shared_css_cron',
				'type'          => 'true_false',
				'instructions'  => 'When enabled, an hourly cron will check to see if any shared critical CSS needs to be generated.',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => 'Enabled',
				'ui_off_text'   => 'Disabled'
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
				'public'  => true
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
		 * Endeavors to collect all the available templates
		 * on the theme to return as options.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return array
		 */
		public static function templates_as_options() {
			$retval = array();

			foreach( self::post_types_as_options() as $post_type => $post_type_label ) {
				$templates = wp_get_theme()->get_page_templates( null, $post_type );

				foreach( $templates as $template_filename => $template_name ) {
					$retval[$template_filename] = $template_name;
				}
			}

			return $retval;
		}

		/**
		 * Function for setting the choices for the
		 * ucfccss_deferred_rules_post_type field.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $field The ACF Field
		 * @return array The ACF Field
		 */
		public static function get_post_types_choices( $field ) {
			$field['choices'] = self::post_types_as_options();
			return $field;
		}

		/**
		 * Function for setting the choices for the
		 * ucfccss_deferred_rules_taxonomies field.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $field The ACF Field
		 * @return array The ACF Field
		 */
		public static function get_taxonomies_choices( $field ) {
			$field['choices'] = self::taxonomies_as_options();
			return $field;
		}

		/**
		 * Function for setting the choices for the
		 * ucfccss_deferred_rules_templates field.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $field The ACF Field
		 * @return array The ACF Field
		 */
		public static function get_templates_choices( $field ) {
			$field['choices'] = self::templates_as_options();
			return $field;
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

		/**
		 * Helper function to clean up old values in the deferred rules field
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param string The ID of the post being saved.
		 * @return void
		 */
		public static function clean_deferred_rules( $post_id ) {
			if ( $post_id !== 'options' ) return;

			$rules = get_field( 'ucfccss_deferred_rules', 'option' );

			// These are not the fields we're looking for...
			if ( ! $rules ) return;

			foreach( $rules as $idx => $rule ) {
				switch( $rule['object_type'] ) {
					case 'post_type':
						if ( get_option( "options_ucfccss_deferred_rules_{$idx}_taxonomies" , null) !== null ) {
							delete_option( "options_ucfccss_deferred_rules_{$idx}_taxonomies" );
							delete_option( "_options_ucfccss_deferred_rules_{$idx}_taxonomies" );
						} else if ( get_option( "options_ucfccss_deferred_rules_{$idx}_templates", null ) !== null ) {
							delete_option( "options_ucfccss_deferred_rules_{$idx}_templates" );
							delete_option( "_options_ucfccss_deferred_rules_{$idx}_templates" );
						}
						break;
					case 'taxonomy':
						if ( get_option( "options_ucfccss_deferred_rules_{$idx}_post_types", null ) === null ) {
							delete_option( "options_ucfccss_deferred_rules_{$idx}_post_types" );
							delete_option( "_options_ucfccss_deferred_rules_{$idx}_post_types" );
						} else if ( get_option( "options_ucfccss_deferred_rules_{$idx}_templates", null ) !== null ) {
							delete_option( "options_ucfccss_deferred_rules_{$idx}_templates" );
							delete_option( "_options_ucfccss_deferred_rules_{$idx}_templates" );
						}
						break;
					case 'template':
						if ( get_option( "options_ucfccss_deferred_rules_{$idx}_post_types", null ) !== null ) {
							delete_option( "options_ucfccss_deferred_rules_{$idx}_post_types" );
							delete_option( "_options_ucfccss_deferred_rules_{$idx}_post_types" );
						} else if ( get_option( "options_ucfccss_deferred_rules_{$idx}_taxonomies", null ) !== null ) {
							delete_option( "options_ucfccss_deferred_rules_{$idx}_taxonomies" );
							delete_option( "_options_ucfccss_deferred_rules_{$idx}_taxonomies" );
						}
						break;
				}
			}
		}
	}
}
