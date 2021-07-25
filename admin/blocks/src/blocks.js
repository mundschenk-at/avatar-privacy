/**
 * Blocks for the WordPress block editor.
 *
 * This file is part of Avatar Privacy.
 *
 * @file     This file registers the blocks included with the Avatar Privacy plugin.
 * @author   Peter Putzer <github@mundschenk.at>
 * @license	 GPL-2.0-or-later
 * @since    2.3.0
 * @requires Gutenberg 4.3
 */

'use strict';

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import * as frontendForm from './frontend-form';
import * as avatar from './avatar';

/**
 * Registers all our blocks.
 */
[ frontendForm, avatar ].forEach( ( block ) => {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	registerBlockType( name, {
		...metadata,
		...settings,
	} );
} );
