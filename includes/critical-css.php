<?php
namespace UCF\Critical_CSS\Includes\Critical_CSS {

	use UCF\Critical_CSS\Admin;

	/**
	 * Returns stored critical CSS to utilize for the provided
	 * post/term object.
	 *
	 * @since 0.1.0
	 * @author Jo Dickson
	 * @param object $obj WordPress post or term object; defaults to the
	 *                    current queried object if not provided
	 * @return string
	 */
	function get_critical_css( $obj=null ) {
		if ( ! $obj ) {
			$obj = get_queried_object();
		}
		if ( ! $obj ) return '';

		$match = Admin\Utilities::get_matching_rule( $obj );

		if ( $match ) {
			switch( $match['object_type'] ) {
				case 'post_meta':
					return get_post_meta( $obj->ID, $match['object_name'], true );
				case 'term_meta':
					return get_term_meta( $obj->term_id, $match['object_name'], true );
				case 'option':
					return get_option( $match['object_name'], null );
				default:
					break;
			}
		}

		return '';
	}

	/**
	 * Inserts critical CSS for the provided post/term object
	 * into the document <head>.
	 *
	 * For use with the `wp_head` action hook.
	 *
	 * @since 0.1.0
	 * @author Jo Dickson
	 * @return void
	 */
	function insert_in_head() {
		$critical_css = get_critical_css();
		if ( $critical_css ) :
	?>
<style id="ucfccss-critical-css"><?php echo $critical_css; ?></style>
	<?php
		endif;
	}

}
