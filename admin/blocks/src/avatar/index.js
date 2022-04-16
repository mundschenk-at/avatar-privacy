/**
 * Avatar block for the WordPress block editor.
 *
 * This file is part of Avatar Privacy.
 *
 * @file    This file provides the Avatar block.
 * @author  Peter Putzer <github@mundschenk.at>
 * @license	GPL-2.0-or-later
 * @since   2.3.0
 */

'use strict';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
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

	edit,
	save: () => {
		// Intentionally empty because this is a dynamic block
	},
};
