'use strict';

/**
 * Avatar Privacy Frontend Form Block
 *
 * The block is rendered server-side for consistency with the classic editor shortcode.
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, PanelRow, RangeControl, ServerSideRender, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/editor';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
registerBlockType(
	'avatar-privacy/form', // Name of the block with a required name space
	{
		title: __( 'Avatar Privacy Form' ), // Title, displayed in the editor
		icon: 'admin-users', // Icon, from dashicons
		category: 'common', // Block category, where the block will be added in the editor

		/**
		 * An object containing the block attributes and their storage location.
		 *
		 * @type {Object}
		 */
		attributes: {
			avatar_size: {
				type: 'integer',
				default: 96,
			},
			show_descriptions: {
				type: 'boolean',
				default: true,
			},
		},

		/**
		 * Edits the block attributes.
		 *
		 * Makes the markup for the editor interface.
		 *
		 * @param {Object} props {
		 *     attributes    - The block attributes.
		 *     className     - Optional class name from the block editor.
		 *     setAttributes - The attribute setter function.
		 * }
		 *
		 * @return {Object} ECMAScript JSX Markup for the editor
		 */
		edit: ( { attributes, className, setAttributes } ) => {
			return (
				<Fragment>
					<InspectorControls>
						<PanelBody title={ __( 'Form' ) } >
							<PanelRow>
								<RangeControl
									label={ __( 'Avatar Size' ) }
									value={ attributes.avatar_size }
									initialPosition={ attributes.avatar_size }
									onChange={ ( newSize ) => setAttributes( { avatar_size: newSize } ) }
									min={ 48 }
									max={ 240 }
								/>
							</PanelRow>
							<PanelRow>
								<ToggleControl
									label={ __( 'Show Descriptions' ) }
									checked={ !! attributes.show_descriptions }
									onChange={ () => setAttributes( { show_descriptions: ! attributes.show_descriptions } ) }
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
		},

		/**
		 * Saves the rendered block.
		 *
		 * As rendering is done on the PHP side, we always return null.
		 *
		 * @return {Object} ECMAScript JSX Markup for the site
		 */
		save: () => {
			return null;
		},
	}
);
