/**
 * Frontend Form block for the WordPress block editor.
 *
 * This file is part of Avatar Privacy.
 *
 * @file    This file provides the Frontend Form block.
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
 * The Frontend Form block.
 *
 * The block is rendered server-side to be current (avatars can change frequently).
 */
export const settings = {
	title: __( 'Avatar Privacy Form', 'avatar-privacy' ),

	supports: {
		html: false,
		multiple: false,
		reusable: false,
	},

	edit,
	save: () => {
		// Intentionally empty because this is a dynamic block
	},
};
