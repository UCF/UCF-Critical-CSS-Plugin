<?php
namespace UCF\Critical_CSS\API {
	/**
	 * Provides the necessary functions for the API
	 * portion of the plugin.
	 */
	class Critical_CSS_API extends \WP_REST_Controller  {
		/**
		 * Registers the rest routes for the critical CSS API
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return void
		 */
		public static function register_rest_routes() {
			$root    = 'ucfccss';
			$version = 'v1';

			/**
			 * Rest route responsible for updating a single object
			 */
			register_rest_route( "$root/$version", "/update/single", array(
				'methods'             => \WP_REST_Server::CREATABLE, // Support POST only
				'callback'            => array( __CLASS__, 'update_single' ),
				'permission_callback' => array( __CLASS__, 'get_permissions' )
			) );

			register_rest_route( "$root/$version", "/update/template", array(
				'methods'             => \WP_REST_Server::EDITABLE, // Support POST only
				'callback'            => array( __CLASS__, 'update_template' ),
				'permission_callback' => array( __CLASS__, 'get_permissions' )
			) );
		}

		/**
		 * Determines if the user is allowed to execute
		 * the functions.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @return bool
		 */
		public static function get_permissions() {
			return true;
		}

		/**
		 * Handler for the /update/single endpoint.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param WP_REST_Request The incoming REST request
		 * @return WP_REST_Response
		 */
		public static function update_single( $request ) {
			$retval = array(
				'result'  => 'success',
				'message' => ''
			);

			$body = $request->get_body();
			$data = json_decode( $body );

			// Make sure the JSON is valid
			if ( ! $data ) {
				$retval['result'] = 'error';
				$retval['message'] = 'There was an error parsing the request body';

				return new \WP_REST_Response( $retval, 400 );
			}

			// Make sure the CSRF is valid
			$csrf        = $data->input->args->meta->csrf ?? null;
			$object_type = $data->input->args->meta->object_type ?? null;
			$object_id   = $data->input->args->meta->object_id ?? null;

			if ( $csrf && $object_type && $object_id ) {
				$token = get_transient( $csrf );

				if ( ! $token ||
					$token['object_type'] !== $object_type ||
					$token['object_id'] !== $object_id )
				{
					$retval['result'] = 'error';
					$retval['message'] = 'CSRF Token failure.';
					return new \WP_REST_Response( $retval, 403 );
				}
			}

			// Make sure we were actually sent CSS
			if ( $data->result === null ) {
				$retval['result'] = 'error';
				$retval['message'] = 'There was no critical css in the request';

				return new \WP_REST_Response( $retval, 400 );
			}

			$success = false;

			if ( $object_type === 'post' ) {
				$success = update_post_meta(
					$object_id,
					'object_critical_css',
					$data->result
				);
			} else if ( $object_type === 'term' ) {
				$success = update_term_meta(
					$object_id,
					'object_critical_css',
					$data->result
				);
			}

			// Delete the transient
			delete_transient( $csrf );

			if ( $success === false ) {
				$retval['result'] = 'error';
				$retval['message'] = "There was an error updating the $object_type meta";
				return new \WP_REST_Response( $retval, 500 );
			}

			return new \WP_REST_Response( $data->result, 200 );
		}

		/**
		 * Handler for the /update/template endpoint.
		 * @author Jim Barnes
		 * @since 0.1.0
		 * @param WP_REST_Request The incoming REST request
		 * @return WP_REST_Response
		 */
		public static function update_template( $request ) {
			$retval = 'Success';

			$body = $request->get_body();

			return new \WP_REST_Response( $retval, 200 );
		}
	}
}
