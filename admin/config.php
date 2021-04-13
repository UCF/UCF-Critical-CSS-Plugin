<?php
namespace UCF\Critical_CSS\Admin {
	/**
	 * Defines the plugin settings
	 */
	class Config {
		public static
			$option_prefix = 'ucf_critical_css_',
			$option_defaults = array(
				'enable_deferred_css'        => false
			);

		/**
		 * Creates options via the WP Options API.
		 * Meant to be run on plugin activation
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function add_options() {
			$defaults = self::$option_defaults;

			// Add all options that have defaults
			foreach( $defaults as $short_name => $default_val ) {
				add_option(
					self::$option_prefix . $short_name,
					$default_val
				);
			}

			// Manually add other options below
		}

		/**
		 * Deletes options via the WP Options API.
		 * Meant to be run on deactivation.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function delete_options() {
			$defaults = self::$option_defaults;

			// Delete all options that have defaults
			foreach( $defaults as $short_name => $default_val ) {
				delete_option( self::$option_prefix . $short_name );
			}

			// Manually delete other options below
		}

		/**
		 * Returns a list of default plugin options. Applies any overridden
		 * default values set within the options page.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function get_option_defaults() {
			$defaults = self::$option_defaults;

			$configurable_defaults = array();

			foreach( $defaults as $short_name => $default_val ) {
				$configurable_defaults[$short_name] = get_option( self::$option_prefix . $short_name );
			}

			$configurable_defaults = self::format_options( $configurable_defaults );

			$defaults = array_merge( $defaults, $configurable_defaults );

			return $defaults;
		}

		/**
		 * Returns an array with plugin defaults applied.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $list
		 * @param bool $list_keys_only Modifies results to only return array key
		 *                             values present in $list.
		 * @return array
		 */
		public static function apply_option_defaults( $list, $list_keys_only = false ) {
			$defaults = self::get_option_defaults();
			$options = array();

			if ( $list_keys_only ) {
				foreach( $list as $key=>$val ) {
					$options[$key] = ! empty( $val ) ? $val : $defaults[$key];
				}
			} else {
				$options = array_merge( $defaults, $list );
			}

			$options = self::format_options( $options );

			return $options;
		}

		/**
		 * Performs typecasting, sanitization, etc on an array of plugin options.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $list
		 * @return array
		 */
		public static function format_options( $list ) {
			foreach( $list as $key => $val ) {
				switch( $key ) {
					case 'enable_deferred_css':
						$list[$key] = filter_var( $val, FILTER_VALIDATE_BOOLEAN );
					default:
						break;
				}
			}

			return $list;
		}

		/**
		 * Applies formatting to a single option. Intended to be passed to the
		 * option_{$option} hook.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param mixed $value The value to be formatted
		 * @param string $option_name The name of the option to be formatted
		 * @return mixed
		 */
		public static function format_option( $value, $option_name ) {
			$option_name_no_prefix = str_replace( self::$option_prefix, '', $option_name );

			$option_formatted = self::format_options( array( $option_name_no_prefix => $value ) );
			return $option_formatted[$option_name_no_prefix];
		}

		/**
		 * Adds filters for plugin options that apply
		 * out formatting rules to option values.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function add_option_formatting_filters() {
			$defaults = self::$option_defaults;

			foreach( $defaults as $option => $default ) {
				$option_name = self::$option_prefix . $option;
				add_filter( "option_{$option_name}", array( __NAMESPACE__ . '\Config', 'format_option' ), 10, 2 );
			}
		}

		/**
		 * Utility method for returning an option from the WP Options API
		 * or a plugin option default.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param $option_name The name of the option to get
		 * @return mixed
		 */
		public static function get_option_or_default( $option_name ) {
			$option_name_no_prefix = str_replace( self::$option_prefix, '', $option_name );
			$option_name = self::$option_prefix . $option_name_no_prefix;

			$option = get_option( $option_name );
			$option_formatted = self::apply_option_defaults( array(
				$option_name_no_prefix => $option
			), true );

			return $option_formatted[$option_name_no_prefix];
		}

		public static function settings_init() {
			$settings_slug = 'ucf_critical_css';
			$defaults = self::$option_defaults;
			$display_fn = array( __NAMESPACE__ . '\Config', 'display_settings_field' );

			foreach( $defaults as $name => $value ) {
				register_setting(
					$settings_slug,
					self::$option_prefix . $name
				);
			}

			/**
			 * Sections are registered here
			 */
			add_settings_section(
				$settings_slug . '_general',
				__( 'General' ),
				'',
				$settings_slug
			);


			/**
			 * Fields are registered here
			 */
			add_settings_field(
				self::$option_prefix . 'enable_deferred_css', // Setting Name
				'Enable Deferred CSS Styles',                 // Setting Label
				$display_fn,                                  // Display Function
				$settings_slug,                               // The settings page slug
				$settings_slug . '_general',                  // The section slug
				array(
					'label_for'   => self::$option_prefix . 'enable_deferred_css',
					'description' => 'When checked, deferred styles will be used on eligible pages.',
					'type'        => 'checkbox'
				)
			);
		}

		/**
		 * Displays an individual setting's field markup.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param array $args The arguments for the field
		 * @return string The field markup
		 */
		public static function display_settings_field( $args ) {
			$option_name   = $args['label_for'];
			$description   = $args['description'];
			$field_type    = $args['type'];
			$options       = isset ( $args['options'] ) ? $args['options'] : null;
			$current_value = self::get_option_or_default( $option_name );
			$markup        = '';

			switch( $field_type ) {
				case 'checkbox':
					ob_start();
				?>
					<p>Here's some markup</p>
					<input type="checkbox" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>" <?php echo ( $current_value == true ) ? 'checked' : ''; ?>>
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'number':
					ob_start();
				?>
					<input type="number" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>" value="<?php echo $current_value; ?>">
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'select':
					ob_start();
				?>
					<?php if ( $options ) : ?>
					<select id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>">
						<?php foreach ( $options as $value => $text ) : ?>
							<option value="<?php echo $value; ?>" <?php echo ( ( $current_value === false && $value === '' ) || ( $current_value === $value ) ) ? 'selected' : ''; ?>><?php echo $text; ?></option>
						<?php endforeach; ?>
					</select>
					<?php else: ?>
					<p style="color: #d54e21;">There was an error retrieving the choices for this field.</p>
					<?php endif; ?>
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'text':
				default:
					ob_start();
				?>
					<input type="text" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>" value="<?php echo $current_value; ?>">
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
			}

			echo $markup;
		}

		/**
		 * Registers the settings page ti display in the WordPress admin.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return string The resulting page's hook suffix.
		 */
		public static function add_options_page() {
			$page_title = 'UCF Critical CSS Settings';
			$menu_title = 'UCF Critical CSS';
			$capability = 'manage_options';
			$menu_slug  = 'ucf_critical_css';
			$callback   = array(
				__NAMESPACE__ . '\Config',
				'options_page_html'
			);

			return add_options_page(
				$page_title,
				$menu_title,
				$capability,
				$menu_slug,
				$callback
			);
		}

		public static function options_page_html() {
			ob_start();
			var_dump( 'What\'s going on?!' );
		?>
			<div class="wrap">
				<h1><?php echo get_admin_page_title(); ?></h1>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'ucf_critical_css' );
						do_settings_sections( 'ucf_critical_css' );
						submit_button();
					?>
				</form>
			</div>
		<?php
			return ob_get_clean();
		}
	}
}
