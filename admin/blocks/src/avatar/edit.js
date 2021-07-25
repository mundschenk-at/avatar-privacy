/**
 * Avatar block for the WordPress block editor.
 *
 * This file is part of Avatar Privacy.
 *
 * @file    This file provides the edit method for the Avatar block.
 * @author  Peter Putzer <github@mundschenk.at>
 * @license	GPL-2.0-or-later
 * @since   2.2.0
 */

'use strict';

/**
 * WordPress dependencies
 */
import {
	PanelBody,
	PanelRow,
	RangeControl,
	SelectControl,
} from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { InspectorControls } from '@wordpress/editor';
import { Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Renders the markup for editing the block attributes of the Avatar block.
 *
 * @param {Object} props               The block properties.
 * @param {Object} props.attributes    The block attributes.
 * @param {Object} props.setAttributes The attribute setter function.
 * @return {Object} ECMAScript JSX Markup for the editor
 */
export default withSelect(
	// Retrieve WordPress authors.
	( select ) => ( { users: select( 'core' ).getAuthors() } )
)( ( { attributes, setAttributes, users } ) => {
	// The authors list has not finished loading yet.
	if ( ! users || users.length < 1 ) {
		return __( 'Loading…', 'avatar-privacy' );
	}

	//  Set default for user_id.
	attributes.user_id = attributes.user_id || users[ 0 ].id;

	// Find the current user object.
	const findUser = ( userID ) =>
		users.find( ( user ) => parseInt( userID ) === user.id );
	const currentUser = findUser( attributes.user_id );

	// Set the user attribute if missing.
	attributes.user = attributes.user || currentUser;

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Avatar', 'avatar-privacy' ) }>
					<PanelRow>
						<SelectControl
							label={ __( 'User', 'avatar-privacy' ) }
							value={ attributes.user_id }
							options={ users.map( ( user ) => ( {
								label: user.name,
								value: user.id,
							} ) ) }
							onChange={ ( newUser ) =>
								setAttributes( {
									user_id: parseInt( newUser ),
									user: findUser( newUser ),
								} )
							}
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Avatar Size', 'avatar-privacy' ) }
							value={ attributes.avatar_size }
							initialPosition={ attributes.avatar_size }
							onChange={ ( newSize ) =>
								setAttributes( { avatar_size: newSize } )
							}
							min={ 48 }
							max={ 240 }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<img
				width={ attributes.avatar_size }
				src={ attributes.user.avatar_urls[ 96 ] }
				alt={ sprintf(
					/* translators: user display name */
					__( 'Avatar of %s', 'avatar-privacy' ),
					attributes.user.name
				) }
			/>
		</Fragment>
	);
} );
