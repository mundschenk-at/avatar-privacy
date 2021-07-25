/**
 * Frontend Form block for the WordPress block editor.
 *
 * This file is part of Avatar Privacy.
 *
 * @file    This file provides the edit method for the Avatar block.
 * @author  Peter Putzer <github@mundschenk.at>
 * @license	GPL-2.0-or-later
 * @since   2.3.0
 */

'use strict';

/**
 * WordPress dependencies
 */
import {
	PanelBody,
	PanelRow,
	RangeControl,
	ServerSideRender,
	ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/editor';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Renders the markup for editing the block attributes of the Avatar block.
 *
 * @param {Object} props               The block properties.
 * @param {Object} props.attributes    The block attributes.
 * @param {string} props.className     The CSS class to use.
 * @param {Object} props.setAttributes The attribute setter function.
 * @return {Object} ECMAScript JSX Markup for the editor
 */
export default ( { attributes, className, setAttributes } ) => {
	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Form', 'avatar-privacy' ) }>
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
					<PanelRow>
						<ToggleControl
							label={ __(
								'Show Descriptions',
								'avatar-privacy'
							) }
							checked={ !! attributes.show_descriptions }
							onChange={ () =>
								setAttributes( {
									show_descriptions: ! attributes.show_descriptions,
								} )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="avatar-privacy/form"
				attributes={ {
					avatar_size: attributes.avatar_size,
					show_descriptions: attributes.show_descriptions,
					className,
					preview: true,
				} }
			/>
		</Fragment>
	);
};
