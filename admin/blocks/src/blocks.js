'use strict';

/**
 * Avatar Privacy
 *
 * The blocks provided by the Avatar Privacy plugin.
 *
 * @requires Gutenberg 4.3
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import * as frontendForm from './frontend-form';
import * as avatar from './avatar';

// Register all our blocks.
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
