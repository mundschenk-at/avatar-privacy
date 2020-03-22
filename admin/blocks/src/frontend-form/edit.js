'use strict';

/**
 * Avatar Privacy Frontend Form Block edit method
 *
 * The block is rendered server-side for consistency with the classic editor shortcode.
 */

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
