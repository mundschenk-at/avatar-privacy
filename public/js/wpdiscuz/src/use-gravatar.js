/**
 * Compatibility functions for the wpDiscuz plugin.
 *
 * This file is part of Avatar Privacy.
 *
 * @file    This file handles the use_gravatar checkbox for wpDiscuz.
 * @author  Peter Putzer <github@mundschenk.at>
 * @license	GPL-2.0-or-later
 * @since   2.2.0
 */

/**
 * Resets the use_gravatar checkbox after posting a new comment in wpDiscuz.
 */
jQuery( function ( $ ) {
	'use strict';

	const $useGravatarCheckbox = $( '#' + avatarPrivacy.checkbox );

	function resetUseGravatar() {
		const useGravatar =
			Cookies.get( avatarPrivacy.cookie ) !== undefined &&
			'' !== Cookies.get( avatarPrivacy.cookie )
				? 'checked'
				: '';

		$useGravatarCheckbox.prop( 'checked', useGravatar );
	}

	$( document ).on( 'ajaxComplete', resetUseGravatar );
} );
