'use strict';

/**
 * Avatar Privacy Avatar Block
 *
 * The block is rendered server-side to be current (avatars can change frequently).
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, PanelRow, RangeControl, SelectControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { InspectorControls } from '@wordpress/editor';
import { Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * The attributes of the block.
 *
 * @type {Object}
 */
const blockAttributes = {
	avatar_size: {
		type: 'integer',
		default: 96,
	},
	user_id: {
		type: 'integer',
		default: 0,
	},
	align: {
		type: 'string',
		default: '',
	},
	// Just temporary information during editing.
	user: {
		type: 'object',
		source: 'attribute',
		selector: '*',
		default: undefined,
	},
};

/**
 * Edits the block attributes.
 *
 * Makes the markup for the editor interface.
 *
 * @param {Object} props {
 *     attributes    - The block attributes.
 *     setAttributes - The attribute setter function.
 * }
 *
 * @return {Object} ECMAScript JSX Markup for the editor
 */
const edit = withSelect(
	// Retrieve WordPress authors.
	( select ) => ( { users: select( 'core' ).getAuthors() } )
)( ( { attributes, setAttributes, users } ) => {
	// The authors list has not finished loading yet.
	if ( ! users || users.length < 1 ) {
		return __( 'Loading...', 'avatar-privacy' );
	}

	//  Set default for user_id.
	attributes.user_id = attributes.user_id || users[ 0 ].id;

	// Find the current user object.
	const findUser = ( userID ) => users.find( ( user ) => ( parseInt( userID ) === user.id ) );
	const currentUser = findUser( attributes.user_id );

	// Set the user attribute if missing.
	attributes.user = attributes.user || currentUser;

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Avatar', 'avatar-privacy' ) } >
					<PanelRow>
						<SelectControl
							label={ __( 'User', 'avatar-privacy' ) }
							value={ attributes.user_id }
							options={ users.map( ( user ) => ( { label: user.name, value: user.id } ) ) }
							onChange={ ( newUser ) => setAttributes( { user_id: parseInt( newUser ), user: findUser( newUser ) } ) }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Avatar Size', 'avatar-privacy' ) }
							value={ attributes.avatar_size }
							initialPosition={ attributes.avatar_size }
							onChange={ ( newSize ) => setAttributes( { avatar_size: newSize } ) }
							min={ 48 }
							max={ 240 }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<img
				width={ attributes.avatar_size }
				src={ attributes.user.avatar_urls[ 96 ] }
				alt={ sprintf( __( 'Avatar of %s', 'avatar-privacy' ), attributes.user.name ) }
			/>
		</Fragment>
	);
} );

/**
 * Registers and creates block
 *
 * @param {string} Name Name of the block with a required name space
 * @param {object} ObjectArgs Block configuration {
 *      title - Title, displayed in the editor
 *      icon - Icon, from WP icons
 *      category - Block category, where the block will be added in the editor
 *      attributes - Object with all binding elements between the view HTML and the functions
 *      edit function - Returns the markup for the editor interface.
 *      save function - Returns the markup that will be rendered on the site page
 * }
 */
registerBlockType( 'avatar-privacy/avatar', {
	// Metadata.
	title: __( 'Avatar', 'avatar-privacy' ),
	icon: 'admin-users',
	category: 'common',

	// The meat.
	attributes: blockAttributes,
	supports: {
		align: [ 'left', 'center', 'right' ],
		html: false,
	},
	edit,
	save: () => {},
} );
