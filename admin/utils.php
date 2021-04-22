<?php
/**
 * Utilities for sending the requests to generate critical CSS
 */
namespace UCF\Critical_CSS\Admin {
	class Utilities {

		/**
		 * Function to use for the save_post hook.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param int $post_id The ID of the post.
		 * @param WP_Post $post The post object
		 */
		public static function on_save_post( $post_id, $post ) {
			$match = self::get_matching_rule( $post );

			if ( $match && $match['object_type'] === 'post_meta' ) {
				self::request_object_critical_css( $post );
			}
		}

		/**
		 * Function to use for the edit_taxonomy hook.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param int $post_id The ID of the post.
		 * @param WP_Post $post The post object
		 */
		public static function on_edit_taxonomy( $term_id, $term ) {
			$match = self::get_matching_rule( $term );

			if ( $match && $match['object_type'] === 'term_meta' ) {
				self::request_object_critical_css( $term, true );
			}
		}

		/**
		 * Loops through the critical CSS rules and makes requests
		 * for any shared critical CSS values that need to be refreshed.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function update_shared_critical_css() {
			$now = time();
			$rules = get_field( 'ucfccss_deferred_rules', 'option' );

			$value_key_lookup = array(
				'post_type' => 'post_types',
				'taxonomy'  => 'taxonomies',
				'template'  => 'templates'
			);

			foreach( $rules as $rule ) {
				if ( $rule['rule_type'] === 'shared' ) {

					$value_key = isset( $value_key_lookup[$rule['object_type']] ) ?
						$value_key_lookup[$rule['object_type']] :
						null;

					if ( ! $value_key ) continue;

					foreach( $rule[$value_key] as $value ) {
						// Returns the name of the rule we need to check.
						$css_object_key = self::get_shared_rule_name( $rule['object_type'], $value );
						$css_object_exp = "{$css_object_key}_expiration";

						$css = get_option( $css_object_key, null );
						$exp = get_option( $css_object_exp, null );

						if (
							! $css ||
							! $exp ||
							( $css && $exp <= $now )
						) {
							\WP_CLI::log( 'Generating {$css_object_key}...' );
							$object = self::get_first_object_matching_rule( $rule['object_type'], $value );

							if ( $object ) {
								$object_url = $rule['object_type'] === 'taxonomy' ?
									get_term_link( $object ) :
									get_permalink( $object );

								self::request_shared_critical_css( $css_object_key, $object_url );
							}
						}
					}
				}
			}
		}

		/**
		 * Returns the option name for shared rules
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param string $object_type The object type: post_type, taxonomy or template
		 * @param string $object_value The specific post_type, taxonomy or template
		 */
		public static function get_shared_rule_name( $object_type, $object_value ) {
			return "ucfccss_{$object_type}_{$object_value}_critical_css";
		}

		/**
		 * Returns the first post or term based on the input
		 * parameters.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param
		 */
		public static function get_first_object_matching_rule( $object_type, $object_value ) {
			$retval = null;

			if ( in_array( $object_type, array( 'post_type', 'template' ) ) ) {
				$args = array(
					'posts_per_page' => 1,
				);

				if ( $object_type === 'post_type' ) {
					$args['post_type'] = $object_value;
				} else if ( $object_type === 'template' ) {
					$args['meta_key']   = '_wp_page_template';
					$args['meta_value'] = $object_value;
				}

				$results = get_posts( $args );
				$retval = count( $results ) > 0 ? $results[0] : null;

			} else if ( $object_type === 'taxonomy' ) {
				$args = array(
					'number'   => 1,
					'taxonomy' => $object_value
				);

				$results = get_terms( $args );
				$retval = count( $results ) > 0 ? $results[0] : null;
			}

			return $retval;
		}

		/**
		 * Requests critical CSS to be generated for a
		 * post or term.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param WP_Post|WP_Term $object_id The post or term id of the object
		 */
		public static function request_object_critical_css( $object, $is_term = false ) {
			// Let's get the HTML
			$args = array(
				'timeout' => 15,
				'cookies' => array()
			);

			$url = $is_term ?
				get_term_link( $object ) :
				get_permalink( $object );

			// We couldn't find a permalink, so return out.
			if ( ! $url ) return;

			$response = wp_remote_get( $url, $args );

			// We couldn't get the page for whatever reason, return out.
			if ( is_wp_error( $response ) ) return;

			$html = wp_remote_retrieve_body( $response );

			$meta = array(
				'response_url' => get_rest_url( null, 'ucfccss/v1/update/single/' ), // The url of the API. Leaving blank for now.
				'object_type'  => $is_term ? 'term' : 'post',
				'object_id'    => $is_term ? $object->term_id : $object->ID
			);

			$transient_key = 'ucfccss_csrf__' . md5( "{$meta['object_type']}__{$meta['object_id']}" );

			$meta['csrf'] = $transient_key;

			set_transient( $transient_key, $meta, 1200 );

			/**
			 * If the HTML is more than 64kb, set it to null. The URL will be
			 * used instead for the critical process.
			 */
			if ( strlen( $html ) >= UCF_CRITICAL_CSS__MAX_MSG_SIZE ) {
				$html = null;
			}

			$request_body = self::build_critical_css_request( $html, $url, $meta );
			$request_url = get_field( 'critical_css_service_url', 'option' );
			$request_key = get_field( 'critical_css_service_key', 'option' );

			$request_args = array(
				'body' => $request_body
			);

			// Add the request_key if one exists
			if ( ! empty( $request_key ) ) {
				$request_args['headers'] = array(
					'x-functions-key' => $request_key
				);
			}

			$response = wp_remote_post( $request_url, $request_args );

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				error_log( 'Failed to enqueue critical css request' );
			}
		}

		/**
		 * Generated a request to generate shared critical CSS
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param string $object_key The key to use when storing the CSS
		 * @param string $url The URL to use to generate the critical CSS
		 * @return void
		 */
		public static function request_shared_critical_css( $object_key, $url ) {
			$meta = array(
				'response_url' => get_rest_url( null, 'ucfccss/v1/update/shared/' ), // The url of the API. Leaving blank for now.
				'object_type'  => 'shared',
				'object_id'    => $object_key
			);

			$transient_key = 'ucfccss_csrf__' . md5( "{$meta['object_type']}__{$meta['object_id']}" );

			$meta['csrf'] = $transient_key;

			set_transient( $transient_key, $meta, 1200 );

			// Null html for all shared requests
			$request_body = self::build_critical_css_request( null, $url, $meta );
			$request_url = get_field( 'critical_css_service_url', 'option' );
			$request_key = get_field( 'critical_css_service_key', 'option' );

			$request_args = array(
				'body' => $request_body
			);

			// Add the request_key if one exists
			if ( ! empty( $request_key ) ) {
				$request_args['headers'] = array(
					'x-functions-key' => $request_key
				);
			}

			$response = wp_remote_post( $request_url, $request_args );

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				error_log( 'Failed to enqueue critical css request' );
			}
		}

		/**
		 * Builds the critical css request object
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param string $html The HTML to be processed
		 * @return string
		 */
		private static function build_critical_css_request( $html, $url, $meta ) {
			$retval = array();

			$exclude = get_field( 'excluded_css_selectors', 'option' );
			$dimensions = get_field( 'critical_css_dimensions', 'option' );

			$exclude = ! empty( $exclude ) ? explode( "\n", $exclude ) : null;

			$dimensions_formatted = array();

			foreach( $dimensions as $dimension ) {
				$dimensions_formatted[] = array(
					'width'  => $dimension['width'],
					'height' => $dimension['height']
				);
			}

			$retval['args'] = array(
				'dimensions' => $dimensions_formatted,
				'html'       => $html,
				'meta'       => $meta,
				'url'        => $url
			);

			if ( $exclude ) {
				$retval['args']['exclude'] = $exclude;
			}

			$retval = json_encode( $retval );

			return $retval;
		}

		/**
		 * Get an unordered array of the rules, categorized by types
		 * NOTE: Do not use this function is the order of the rules matters
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param string $subset Returns a subset of `individual` or `shared` settings.
		 * @param string $object_type Returns a 1-dimensional array of the $object_types in both
		 * 							  the individual and shared rules.
		 * @return array
		 */
		public static function get_critical_css_rules( $subset = null, $object_type = null ) {
			$retval = array(
				'individual' => array(
					'post_types' => array(),
					'taxonomies' => array(),
					'templates'  => array()
				),
				'shared'     => array(
					'post_types' => array(),
					'taxonomies' => array(),
					'templates'  => array()
				)
			);

			$rules = get_field( 'ucfccss_deferred_rules', 'option' );

			if ( ! is_array( $rules ) ) return null;

			foreach( $rules as $rule ) {
				if ( $rule['rule_type'] === 'individual' ) {
					if ( $rule['object_type'] === 'post_type' ) {
						$retval['individual']['post_types'] += array_values( $rule['post_types'] );
					} else if ( $rule['object_type'] === 'taxonomy' ) {
						$retval['individual']['taxonomies'] += array_values( $rule['taxonomies'] );
					} else if ( $rule['object_type'] === 'template' ) {
						$retval['individual']['templates'] += array_values( $rule['templates'] );
					}
				} else if ( $rule['rule_type'] === 'shared' ) {
					if ( $rule['object_type'] === 'post_type' ) {
						$retval['shared']['post_types'] += array_values( $rule['post_types'] );
					} else if ( $rule['object_type'] === 'taxonomy' ) {
						$retval['shared']['taxonomies'] += array_values( $rule['taxonomies'] );
					} else if ( $rule['object_type'] === 'template' ) {
						$retval['shared']['templates'] += array_values( $rule['templates'] );
					}
				}
			}

			// Pull out only individual or shared results
			if ( $subset && isset( $retval[$subset] ) ) {
				$retval = $retval[$subset];
			}

			// Pull out only post_types, taxonomies or templates
			if ( $object_type ) {
				$collapsed_retval = array();

				foreach( $retval as $subset => $values ) {
					if ( isset( $values[$object_type] ) ) {
						foreach( $values[$object_type] as $value ) {
							$collapsed_retval[] = $value;
						}
					}
				}

				$retval = $collapsed_retval;
			}

			return $retval;
		}

		/**
		 * Returns the matching Critical CSS Rule
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param WP_Post|WP_Term The object to test
		 * @return array|bool An array which includes the rule info. Returns false if no rule found.
		 */
		public static function get_matching_rule( $object ) {
			$is_post = is_a( $object, 'WP_Post' );
			$post_type = $is_post ? $object->post_type : null;
			$taxonomy = ! $is_post ? $object->taxonomy : null;
			$template = $is_post ? get_page_template_slug( $object->ID ) : null;

			/**
			 * It's very important we get the rules directly from ACF
			 * so that the order of the rules is maintained!
			 */
			$rules = get_field( 'ucfccss_deferred_rules', 'option' );

			foreach( $rules as $rule ) {
				// Handle individual and shared first
				if ( $is_post && is_array( $rule['post_types'] ) && in_array( $post_type, $rule['post_types'] ) ) {
					if ( $rule['rule_type'] === 'individual' ) {
						return array(
							'object_type' => 'post_meta',
							'object_name' => 'object_critical_css'
						);
					} else if ( $rule['rule_type'] === 'shared' ) {
						return array(
							'object_type' => 'transient',
							'object_name' => "ucfccss_post_type_{$post_type}_critical_css"
						);
					}
				} else if ( ! $is_post && is_array( $rule['taxonomies'] ) &&  in_array( $taxonomy, $rule['taxonomies'] ) ) {
					if ( $rule['rule_type'] === 'individual' ) {
						return array(
							'object_type' => 'term_meta',
							'object_name' => 'object_critical_css'
						);
					} else if ( $rule['rule_type'] === 'shared' ) {
						return array(
							'object_type' => 'transient',
							'object_name' => "ucfccss_taxonomy_{$taxonomy}_critical_css"
						);
					}
				}

				if ( $is_post && is_array( $rule['templates'] ) && in_array( $template, $rule['templates'] ) ) {
					if ( $rule['rule_type'] === 'individual' ) {
						return array(
							'object_type' => 'post_meta',
							'object_name' => 'object_critical_css'
						);
					} else if ( $rule['rule_type'] === 'shared' ) {
						return array(
							'object_type' => 'transient',
							'object_name' => "ucfccss_template_{$template}_critical_css"
						);
					}
				}
			}

			return false;
		}
	}
}
