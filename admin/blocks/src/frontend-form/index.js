'use strict';

/**
 * Avatar Privacy Frontend Form Block
 *
 * The block is rendered server-side for consistency with the classic editor shortcode.
 */

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
