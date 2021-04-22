<?php
namespace UCF\Critical_CSS\Includes\Deferred_Styles {

	use UCF\Critical_CSS\Includes\Critical_CSS;

	/**
	 * Returns whether or not deferred style usage is enabled
	 * at the plugin level.
	 *
	 * @since 0.1.0
	 * @author Jo Dickson
	 * @return boolean
	 */
	function enabled_globally() {
		return filter_var( get_option( 'options_enable_deferred_styles_global', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Action that modifies enqueued stylesheets to load asynchronously
	 * when critical CSS is available for the queried object.
	 *
	 * For use with the `style_loader_tag` action hook.
	 *
	 * @since 0.1.0
	 * @author Jo Dickson
	 * @param string $html The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @param string $href The stylesheet's source URL.
	 * @param string $media The stylesheet's media attribute.
	 * @return string The modified link tag markup
	*/
	function defer_enqueued_styles( $html, $handle, $href, $media ) {
		$critical_css = Critical_CSS\get_critical_css();

		if ( $critical_css ) {
			$exclude_option = get_field( 'deferred_exceptions', 'option' );
			$exclude = $exclude_option ? array_filter( array_map( 'trim', explode( "\n", $exclude_option ) ) ) : array();

			if ( ! in_array( $handle, $exclude ) && $media !== 'print' ) {
				$media_replaced = str_replace( 'media=\'' . $media . '\'', 'media=\'print\' onload=\'this.media="' . $media . '"\'', $html );
				$html = $media_replaced . '<noscript>' . $html . '</noscript>';
			}
		}

		return $html;
	}

}
