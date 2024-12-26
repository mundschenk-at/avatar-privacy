/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
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
 * @file    This file provides the Avatar block.
 * @author  Peter Putzer <github@mundschenk.at>
 * @license	GPL-2.0-or-later
 * @since   2.3.0
 */

'use strict';

// WordPress
import { __ } from '@wordpress/i18n';

// Type checking
import PropTypes from 'prop-types';

// Block parts
import edit from './edit';
import metadata from './block.json';

const { name } = metadata;

export { metadata, name };

/**
 * The Avatar block.
 *
 * The block is rendered server-side to be current (avatars can change frequently).
 */
export const settings = {
	title: __( 'Avatar', 'avatar-privacy' ),

	supports: {
		align: [ 'left', 'center', 'right' ],
		html: false,
	},

	propTypes: {
		attributes: PropTypes.objectOf(
			PropTypes.shape( {
				user_id: PropTypes.number,
				avatar_size: PropTypes.number.isRequired,
				user: PropTypes.objectOf(
					PropTypes.shape( {
						name: PropTypes.string,
						avatar_urls: PropTypes.array,
					} ),
				),
			} )
		).isRequired,
		setAttributes: PropTypes.func.isRequired,
	},

	edit,
	save: () => {
		// Intentionally empty because this is a dynamic block
	},
};
