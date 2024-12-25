/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
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
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
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
									show_descriptions:
										! attributes.show_descriptions,
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
