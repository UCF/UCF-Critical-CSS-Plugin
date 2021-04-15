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
			self::request_object_critical_css( $post );
		}

		/**
		 * Function to use for the edit_taxonomy hook.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param int $post_id The ID of the post.
		 * @param WP_Post $post The post object
		 */
		public static function on_edit_taxonomy( $term_id, $term ) {
			self::request_object_critical_css( $term, true );
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
				'response_url' => '', // The url of the API. Leaving blank for now.
				'job_type'     => 'object', // We'll look for specific things for this job type
				'object_type'  => $is_term ? 'term' : 'post',
				'object_id'    => $is_term ? $object->term_id : $object->ID
			);

			/**
			 * If the HTML is more than 64kb, set it to null. The URL will be
			 * used instead for the critical process.
			 */
			if ( strlen( $html ) >= UCF_CRITICAL_CSS__MAX_MSG_SIZE ) {
				$html = null;
			}

			$request_body = self::build_critical_css_request( $html, $url, $meta );
			$request_url = get_field( 'critical_css_service_url', 'option' );

			$response = wp_remote_post( $request_url, array(
				'body' => $request_body
			) );

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
	}
}
