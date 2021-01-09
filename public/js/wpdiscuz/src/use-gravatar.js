/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2021 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * ***
 *
 * @file    This file handles the use_gravatar checkbox for wpDiscuz.
 * @author  Peter Putzer <github@mundschenk.at>
 * @since   2.2.0
 */

/**
 * Resets the use_gravatar checkbox after posting a new comment in wpDiscuz.
 *
 * @author Peter Putzer <github@mundschenk.at>
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

	$( document ).bind( 'ajaxComplete', function () {
		resetUseGravatar();
	} );
} );
