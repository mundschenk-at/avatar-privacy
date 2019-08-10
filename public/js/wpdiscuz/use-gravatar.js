/**
 * Resets the use_gravatar checkbox to the current value after posting a new comment.
 *
 */

/* global avatarPrivacy jQuery Cookies */
/* eslint no-var: 0 */

jQuery( function( $ ) {
	'use strict';

	var $useGravatarCheckbox = $( '#' + avatarPrivacy.checkbox );

	var resetUseGravatar = function() {
		var useGravatar = ( Cookies.get( avatarPrivacy.cookie ) !== undefined && '' !== Cookies.get( avatarPrivacy.cookie ) ) ? 'checked' : '';

		$useGravatarCheckbox.prop( 'checked', useGravatar );
	};

	$( document ).bind( 'ajaxComplete', function() {
		resetUseGravatar();
	} );
} );
