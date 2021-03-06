'use strict';

/**
 * Avatar Privacy Avatar Block
 *
 * The block is rendered server-side to be current (avatars can change frequently).
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
