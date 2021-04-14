<?php
namespace UCF\Critical_CSS\Includes\Critical_CSS {

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

		// TODO will need to add some logic here that determines
		// if generic post/term template critical CSS should be
		// returned instead of object-specific critical CSS

		return get_field( 'object_critical_css', $obj );
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
